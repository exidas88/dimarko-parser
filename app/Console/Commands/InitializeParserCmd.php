<?php

namespace App\Console\Commands;

use App\Jobs\ParseAuctionsJob;
use App\Jobs\ParseAuctionsPageJob;
use Illuminate\Console\Command;

class InitializeParserCmd extends Command
{
    protected $signature = 'parser:initialize';
    protected $description = 'Initialize HTML DOM parsing process';

    public function handle(): void
    {
        ParseAuctionsJob::dispatch(page: 1);
    }
}
