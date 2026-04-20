<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdatePrioritiesCommand extends Command
{
    protected $signature = 'requests:update-priorities';
    protected $description = 'Deprecated — priority/deadline system removed';

    public function handle(): int
    {
        $this->warn('This command is deprecated. The deadline/priority system has been removed.');
        return self::SUCCESS;
    }
}
