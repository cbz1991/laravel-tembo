<?php

namespace Cabuz\Tembo\Commands;

use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Tembo;
use Illuminate\Console\Command;

class CheckCommand extends Command
{
    protected $signature = 'tembo:check';

    protected $description = 'Check that the Tembo API key works';

    public function handle(Tembo $tembo): int
    {
        try {
            $user = $tembo->me();
        } catch (TemboException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Connected to Tembo as %s.',
            trim(($user['name'] ?? '').' <'.($user['email'] ?? '?').'>'),
        ));

        return self::SUCCESS;
    }
}
