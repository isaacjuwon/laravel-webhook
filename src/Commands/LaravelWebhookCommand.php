<?php

namespace Isaacjuwon\LaravelWebhook\Commands;

use Illuminate\Console\Command;

class LaravelWebhookCommand extends Command
{
    public $signature = 'laravel-webhook';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
