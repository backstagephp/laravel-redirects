<?php

namespace Vormkracht10\Redirects\Commands;

use Illuminate\Console\Command;

class RedirectsCommand extends Command
{
    public $signature = 'laravel-redirects';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
