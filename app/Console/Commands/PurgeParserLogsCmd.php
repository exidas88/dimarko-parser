<?php

namespace App\Console\Commands;

use App\Models\ParserLog;
use Illuminate\Console\Command;

class PurgeParserLogsCmd extends Command
{
    protected $signature = 'auctions:purge-logs {--months=}';
    protected $description = 'Purge parser logs older than defined period of time.';

    public function handle(): void
    {
        $months = $this->option('months') ?? config('parser.purge_logs_months');
        $olderThan = now()->subMonths($months)->toDateString();

        ParserLog::query()
            ->whereDate(ParserLog::CREATED_AT, '<', $olderThan)
            ->delete();

        $this->info('Log data has been purged.');
    }
}
