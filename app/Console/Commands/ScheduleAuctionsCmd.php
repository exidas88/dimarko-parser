<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Jobs\ScheduleAuctionsJob;
use App\Repositories\PageScheduleRepository;
use Illuminate\Console\Command;

class ScheduleAuctionsCmd extends Command
{
    protected const DEFAULT_LIMIT = 5;
    protected const ARGUMENT_TYPE = 'type';
    protected const OPTION_LIMIT = 'limit';

    protected $signature = 'auctions:schedule {' . self::ARGUMENT_TYPE . '} {--'.self::OPTION_LIMIT.'=}';
    protected $description = 'Initialize synchronization to process auctions.';

    public function handle(): void
    {
        $limit = $this->option(self::OPTION_LIMIT) ?? self::DEFAULT_LIMIT;
        $type = $this->resolveType();

        $start = self::lastProcessedPage($type) + 1;
        $finish = $start + $limit;

        for ($page = $start; $page <= $finish; $page++) {
            ScheduleAuctionsJob::dispatch(type: $type, page: $page);
        }
    }

    protected static function lastProcessedPage(AuctionActType $type): int
    {
        $pageSchedule = PageScheduleRepository::create($type);

        return $pageSchedule->page;
    }

    protected function resolveType(): AuctionActType
    {
        $type = $this->argument(self::ARGUMENT_TYPE);
        $enum = AuctionActType::tryFrom($type);

        if (!$enum) {
            $this->error('Invalid type, valid options: ' . implode('|', AuctionActType::asArray()));
            die;
        }

        return $enum;
    }
}
