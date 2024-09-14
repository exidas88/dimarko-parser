<?php

namespace App\Repositories;

use App\Enums\AuctionActType;
use App\Models\Schedule;

class ScheduleRepository
{
    public static function create(string $actId, AuctionActType $type): void
    {
        Schedule::query()->updateOrCreate([
            Schedule::ACT_ID => $actId,
            Schedule::TYPE => $type
        ]);
    }
}