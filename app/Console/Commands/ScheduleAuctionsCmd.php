<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Exceptions\UnsupportedAuctionTypeException;
use App\Jobs\AuctionListJob;
use Illuminate\Console\Command;

class ScheduleAuctionsCmd extends Command
{
    protected const ARGUMENT_TYPE = 'type';

    protected $signature = 'parse:auctions {'.self::ARGUMENT_TYPE.'}';
    protected $description = 'Initialize synchronization to process new auctions.';

    public function handle(): void
    {
        $type = $this->argument(self::ARGUMENT_TYPE);
        $typeEnum = AuctionActType::tryFrom($type);

        if (!$typeEnum) {
            $this->error('Invalid auction type. Valid options: ' . implode('|',AuctionActType::asArray()));
            return;
        }

        AuctionListJob::dispatch(type: $typeEnum, page: 1);
    }
}
