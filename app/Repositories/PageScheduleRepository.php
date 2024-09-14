<?php

namespace App\Repositories;

use App\Enums\AuctionActType;
use App\Models\PageSchedule;
use App\Models\Schedule;

class PageScheduleRepository
{
    public static function delete(?AuctionActType $type): void
    {
        PageSchedule::query()
            ->where(Schedule::TYPE, $type)
            ->delete();
    }

    public static function create(?AuctionActType $type, int $page = 1): PageSchedule
    {
        return PageSchedule::query()->firstOrCreate(
            [PageSchedule::TYPE => $type],
            [PageSchedule::PAGE => $page]
        );
    }
}