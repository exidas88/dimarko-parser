<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Exceptions\UnsupportedAuctionTypeException;
use App\Jobs\AuctionListJob;
use Illuminate\Console\Command;

class SyncNewAuctionsCmd extends Command
{
    protected const ARGUMENT_TYPE = 'type';
    protected const OPTION_MONTHS = 'months';

    protected $signature = 'parse:auctions {'.self::ARGUMENT_TYPE.'} {--'.self::OPTION_MONTHS.'=}';
    protected $description = 'Initialize synchronization to process new auctions.';

    public function handle(): void
    {
        $months = $this->option(self::OPTION_MONTHS);
        $type = $this->argument(self::ARGUMENT_TYPE);

        $typeEnum = AuctionActType::tryFrom($type);

        if (!$typeEnum) {
            $this->error('Invalid auction type. Valid options: ' . implode('|',AuctionActType::asArray()));
            return;
        }

        AuctionListJob::dispatch(type: $typeEnum, page: 1, months: $months);
    }
}
