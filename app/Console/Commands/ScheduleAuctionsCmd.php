<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Exceptions\DailyLimitReachedException;
use App\Jobs\ScheduleAuctionsJob;
use App\Repositories\PageScheduleRepository;
use Illuminate\Console\Command;

class ScheduleAuctionsCmd extends Command
{
    protected const DEFAULT_LIMIT = 1;
    protected const OPTION_TYPE = 'type';
    protected const OPTION_LIMIT = 'limit';

    protected $signature = 'auctions:schedule {--' . self::OPTION_TYPE . '=} {--'.self::OPTION_LIMIT.'=}';
    protected $description = 'Initialize synchronization to process auctions.';

    public function handle(): void
    {
        $limit = $this->option(self::OPTION_LIMIT) ?? self::DEFAULT_LIMIT;

        try {
            $type = $this->resolveType();
            $start = $this->resolvePage();
        } catch (DailyLimitReachedException) {
            $this->info('Daily limit has been reached.');
            return;
        }

        $finish = $start + $limit;
        $start++; // Start from the next page

        for ($page = $start; $page <= $finish; $page++) {
            ScheduleAuctionsJob::dispatch(type: $type, page: $page);
        }
    }

    /**
     * @throws DailyLimitReachedException
     */
    protected function resolvePage(): int
    {
        $pageSchedule = PageScheduleRepository::current() ?? PageScheduleRepository::create();

        return $pageSchedule->page;
    }

    /**
     * @throws DailyLimitReachedException
     */
    protected function resolveType(): AuctionActType
    {
        $type = $this->option(self::OPTION_TYPE) ?? PageScheduleRepository::currentType();
        $enum = AuctionActType::tryFrom($type);

        if (!$enum) {
            $this->error('Invalid type, valid options: ' . implode('|', AuctionActType::asArray()));
            die;
        }

        return $enum;
    }
}
