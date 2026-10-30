<?php

namespace Cabuz\Tembo\Support;

/**
 * Turns live "delta" frames into readable terminal output: the agent's text
 * as it is written, one line per tool call with its output, and setup steps.
 *
 * Agents stream in different formats: OpenAI Responses events (Codex, fx),
 * Anthropic message events (Pi), or Tembo's own "single" / "delta_group"
 * wrappers around them (Claude Code, OpenCode). Unknown events are skipped.
 * Keep one instance per stream: it remembers tool calls across frames.
 */
class LiveOutput
{
    /**
     * Lines of tool output shown before truncating.
     */
    public int $maxToolOutputLines = 10;

    protected bool $atLineStart = true;

    /**
     * Tool calls already printed (and finished), by item ID.
     *
     * @var array<string, array{started: bool, finished: bool}>
     */
    protected array $tools = [];

    /**
     * Anthropic tool_use blocks being streamed, by content block index.
     *
     * @var array<int, array{name: string, input: string}>
     */
    protected array $anthropicTools = [];

    /**
     * Setup steps already printed.
     *
     * @var array<string, true>
     */
    protected array $steps = [];

    /**
     * The text to write for one delta frame's data ('' when there is nothing to show).
     *
     * @param  array<string, mixed>  $delta  The frame's data: event, message, provider...
     */
    public function render(array $delta): string
    {
        $event = (string) ($delta['event'] ?? '');
        $message = json_decode((string) ($delta['message'] ?? ''), true);
        $message = is_array($message) ? $message : [];

        return match ($event) {
            'single' => $this->unwrap($message),
            'delta_group' => $this->toolUpdate($message),
            default => $this->providerEvent($event, $message),
        };
    }

    /**
     * Tembo's "single" wraps one provider event.
     *
     * @param  array<string, mixed>  $message
     */
    protected function unwrap(array $message): string
    {
        $inner = $message['event'] ?? null;

        if (! is_array($inner)) {
            return '';
        }

        $payload = json_decode((string) ($inner['message'] ?? ''), true);

        return $this->providerEvent((string) ($inner['event'] ?? ''), is_array($payload) ? $payload : []);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function providerEvent(string $event, array $message): string
    {
        switch ($event) {
            // OpenAI Responses
            case 'response.output_text.delta':
                return $this->text((string) ($message['delta'] ?? ''));

            case 'response.output_item.done':
                $item = (array) ($message['item'] ?? []);

                return ($item['type'] ?? null) === 'function_call'
                    ? $this->toolStart((string) ($item['id'] ?? ''), (string) ($item['name'] ?? 'tool'), (string) ($item['arguments'] ?? ''))
                    : '';

                // Anthropic
            case 'content_block_start':
                $block = (array) ($message['content_block'] ?? []);

                if (($block['type'] ?? null) === 'tool_use') {
                    $this->anthropicTools[(int) ($message['index'] ?? 0)] = ['name' => (string) ($block['name'] ?? 'tool'), 'input' => ''];
                }

                return '';

            case 'content_block_delta':
                $delta = (array) ($message['delta'] ?? []);
                $index = (int) ($message['index'] ?? 0);

                if (($delta['type'] ?? null) === 'text_delta') {
                    return $this->text((string) ($delta['text'] ?? ''));
                }

                if (($delta['type'] ?? null) === 'input_json_delta' && isset($this->anthropicTools[$index])) {
                    $this->anthropicTools[$index]['input'] .= (string) ($delta['partial_json'] ?? '');
                }

                return '';

            case 'content_block_stop':
                $index = (int) ($message['index'] ?? 0);
                $tool = $this->anthropicTools[$index] ?? null;
                unset($this->anthropicTools[$index]);

                return $tool === null ? '' : $this->toolStart('anthropic-'.count($this->tools), $tool['name'], $tool['input']);

                // End of a turn
            case 'response.completed':
            case 'message_stop':
            case 'agent_turn_finished':
                return $this->newline();

            default:
                return '';
        }
    }

    /**
     * Tembo's "delta_group": a tool call (or a setup step) and its result so far.
     *
     * @param  array<string, mixed>  $message
     */
    protected function toolUpdate(array $message): string
    {
        $name = (string) ($message['toolName'] ?? '');
        $input = (string) ($message['accumulatedText'] ?? '');

        if ($name === 'agent_setup_log') {
            $step = json_decode($input, true);

            if (! is_array($step) || ! isset($step['step'])) {
                return '';
            }

            $line = trim($step['step'].' '.($step['status'] ?? ''));

            if (isset($this->steps[$line])) {
                return '';
            }

            $this->steps[$line] = true;

            return $this->newline().'· '.$line."\n";
        }

        $id = (string) ($message['itemId'] ?? $name);
        $result = (array) ($message['toolResult'] ?? []);
        $output = '';

        if ($name !== '' && ! in_array(trim($input), ['', '{}'], true)) {
            $output .= $this->toolStart($id, $name, $input);
        }

        $status = (string) ($result['status'] ?? '');

        if (in_array($status, ['completed', 'failed', 'error'], true) && ! ($this->tools[$id]['finished'] ?? false)) {
            $this->tools[$id] = ['started' => true, 'finished' => true];
            $output .= $this->toolResult((string) ($result['output'] ?? ''), $status !== 'completed');
        }

        return $output;
    }

    protected function toolStart(string $id, string $name, string $input): string
    {
        if ($this->tools[$id]['started'] ?? false) {
            return '';
        }

        $this->tools[$id] = ['started' => true, 'finished' => false];
        $summary = $this->summarize($input);

        return $this->newline().'→ '.$name.($summary !== '' ? ': '.$summary : '')."\n";
    }

    protected function toolResult(string $output, bool $failed): string
    {
        $lines = preg_split('/\R/', rtrim($output)) ?: [];
        $lines = array_values(array_filter($lines, fn ($line) => $line !== ''));

        if ($lines === []) {
            return $failed ? "  (failed)\n" : '';
        }

        $shown = array_slice($lines, 0, $this->maxToolOutputLines);
        $more = count($lines) - count($shown);
        $text = '  '.implode("\n  ", $shown)."\n";

        if ($more > 0) {
            $text .= "  … {$more} more lines\n";
        }

        return $text;
    }

    /**
     * The most useful part of a tool's JSON input: its command, path or query.
     */
    protected function summarize(string $input): string
    {
        $data = json_decode($input, true);

        if (! is_array($data)) {
            return mb_strimwidth(trim($input), 0, 100, '…');
        }

        // fx nests its arguments one level down: {"request": {"command": ...}}.
        foreach ([$data, ...array_filter($data, 'is_array')] as $candidate) {
            foreach (['command', 'cmd', 'file_path', 'path', 'pattern', 'query', 'url', 'description'] as $key) {
                if (isset($candidate[$key]) && is_scalar($candidate[$key])) {
                    return mb_strimwidth(trim((string) $candidate[$key]), 0, 100, '…');
                }
            }
        }

        return $data === [] ? '' : mb_strimwidth((string) json_encode($data, JSON_UNESCAPED_SLASHES), 0, 100, '…');
    }

    protected function text(string $text): string
    {
        if ($text !== '') {
            $this->atLineStart = str_ends_with($text, "\n");
        }

        return $text;
    }

    /**
     * A line break, unless the cursor already is at the start of a line.
     */
    protected function newline(): string
    {
        if ($this->atLineStart) {
            return '';
        }

        $this->atLineStart = true;

        return "\n";
    }
}
