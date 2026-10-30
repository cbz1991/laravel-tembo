<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Support\LiveOutput;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Frames shaped like the ones Tembo streamed for each agent runtime.
 */
class LiveOutputTest extends BaseTestCase
{
    public function test_openai_responses_text_and_function_calls(): void
    {
        $this->assertSame("I'll run it.\n→ exec_command: echo TOOL_OK\nDONE_OK\n", $this->render([
            $this->setupStep('environment_setup', 'started'),
            ['event' => 'response.output_text.delta', 'provider' => 'openai_responses', 'message' => '{"delta":"I\'ll run "}'],
            ['event' => 'response.output_text.delta', 'provider' => 'openai_responses', 'message' => '{"delta":"it."}'],
            ['event' => 'response.output_item.done', 'provider' => 'openai_responses', 'message' => '{"item":{"id":"fc_1","type":"function_call","name":"exec_command","arguments":"{\"cmd\":\"echo TOOL_OK\"}"}}'],
            ['event' => 'response.output_item.done', 'provider' => 'openai_responses', 'message' => '{"item":{"id":"msg_1","type":"message"}}'],
            ['event' => 'response.output_text.delta', 'provider' => 'openai_responses', 'message' => '{"delta":"DONE_OK"}'],
            ['event' => 'response.completed', 'provider' => 'openai_responses', 'message' => '{}'],
        ], skipSetup: true));
    }

    public function test_anthropic_text_and_tool_use(): void
    {
        $this->assertSame("→ bash: echo TOOL_OK\nDONE_OK\n", $this->render([
            ['event' => 'content_block_start', 'provider' => 'anthropic', 'message' => '{"index":0,"content_block":{"type":"tool_use","name":"bash"}}'],
            ['event' => 'content_block_delta', 'provider' => 'anthropic', 'message' => '{"index":0,"delta":{"type":"input_json_delta","partial_json":"{\"command\": \"echo TOOL"}}'],
            ['event' => 'content_block_delta', 'provider' => 'anthropic', 'message' => '{"index":0,"delta":{"type":"input_json_delta","partial_json":"_OK\"}"}}'],
            ['event' => 'content_block_stop', 'provider' => 'anthropic', 'message' => '{"index":0}'],
            ['event' => 'content_block_start', 'provider' => 'anthropic', 'message' => '{"index":0,"content_block":{"type":"text","text":""}}'],
            ['event' => 'content_block_delta', 'provider' => 'anthropic', 'message' => '{"index":0,"delta":{"type":"text_delta","text":"DONE_OK"}}'],
            ['event' => 'content_block_stop', 'provider' => 'anthropic', 'message' => '{"index":0}'],
            ['event' => 'message_stop', 'provider' => 'anthropic', 'message' => '{}'],
        ]));
    }

    public function test_tembo_wrapped_events_with_tool_results(): void
    {
        $this->assertSame("· setup_finished completed\n→ Bash: echo TOOL_OK\n  TOOL_OK\nDONE_OK\n", $this->render([
            $this->setupStep('setup_finished', 'completed'),
            $this->setupStep('setup_finished', 'completed'),
            $this->tool('toolu_1', 'Bash', '{}', ['status' => 'running']),
            $this->tool('toolu_1', 'Bash', '{"command":"echo TOOL_OK","description":"Print it"}', ['status' => 'running']),
            $this->tool('toolu_1', 'Bash', '{"command":"echo TOOL_OK"}', ['status' => 'running', 'output' => 'TOOL_']),
            $this->tool('toolu_1', 'Bash', '{"command":"echo TOOL_OK"}', ['status' => 'completed', 'output' => "TOOL_OK\n", 'exitCode' => 0]),
            $this->tool('toolu_1', 'Bash', '{"command":"echo TOOL_OK"}', ['status' => 'completed', 'output' => "TOOL_OK\n"]),
            $this->single('content_block_delta', 'anthropic', ['delta' => ['type' => 'text_delta', 'text' => 'DONE_OK']]),
            ['event' => 'single', 'message' => '{"type":"single","event":{"event":"agent_turn_finished","message":"{}"}}'],
        ]));
    }

    public function test_long_tool_output_is_truncated_and_nested_arguments_are_summarized(): void
    {
        $output = new LiveOutput;
        $output->maxToolOutputLines = 2;

        $text = $output->render($this->tool('t1', 'shell', '{"request":{"action":"run","command":"ls"}}', ['status' => 'completed', 'output' => "a\nb\nc\nd"]));

        $this->assertSame("→ shell: ls\n  a\n  b\n  … 2 more lines\n", $text);
    }

    public function test_unknown_events_are_skipped(): void
    {
        $this->assertSame('', $this->render([
            ['event' => 'context_usage', 'provider' => 'anthropic', 'message' => '{"inputTokens":12}'],
            ['event' => 'request_received', 'message' => '{}'],
            ['event' => 'something_new', 'message' => 'not json'],
        ]));
    }

    /**
     * @param  list<array<string, mixed>>  $deltas
     */
    private function render(array $deltas, bool $skipSetup = false): string
    {
        $output = new LiveOutput;
        $text = implode('', array_map(fn ($delta) => $output->render($delta), $deltas));

        return $skipSetup ? (string) preg_replace('/^· .*\n/m', '', $text) : $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function setupStep(string $step, string $status): array
    {
        return $this->tool('', 'agent_setup_log', (string) json_encode(['step' => $step, 'status' => $status]), []);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function tool(string $id, string $name, string $input, array $result): array
    {
        return ['event' => 'delta_group', 'provider' => 'tembo', 'message' => (string) json_encode(array_filter([
            'type' => 'delta_group',
            'itemId' => $id,
            'toolName' => $name,
            'accumulatedText' => $input,
            'toolResult' => $result,
        ], fn ($value) => $value !== '' && $value !== []))];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function single(string $event, string $provider, array $payload): array
    {
        return ['event' => 'single', 'provider' => 'tembo', 'message' => (string) json_encode([
            'type' => 'single',
            'event' => ['event' => $event, 'message' => json_encode($payload), 'provider' => $provider],
        ])];
    }
}
