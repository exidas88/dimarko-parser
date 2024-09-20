<?php

namespace App\Repositories;

use App\Enums\AuctionActType;
use App\Models\Auction;
use App\Models\Schedule;

class ScheduleRepository
{
    public static function create(string $actId, ?string $sourceActId, AuctionActType $type): void
    {
        // Each actId is stored in auction_connections attribute after its successfully stored.
        // None of the auction cases should be processed multiple times, so we check for the
        // existence of current actId in the list and terminate the process if it exists.

        if (AuctionRepository::exists($actId)) {
            return;
        }

        Schedule::query()->updateOrCreate([
            Schedule::ACT_ID => $actId,
            Schedule::SOURCE_ACT_ID => $sourceActId,
            Schedule::TYPE => $type
        ]);
    }

    public static function sourceAuctionId(string $actId): ?string
    {
        $schedule = Schedule::query()
            ->select(Schedule::SOURCE_ACT_ID)
            ->where(Schedule::ACT_ID, $actId)
            ->first();

        return $schedule?->{Schedule::SOURCE_ACT_ID};
    }

    public static function delete(string $actId): void
    {
        Schedule::query()
            ->where(Schedule::ACT_ID, $actId)
            ->delete();
    }
}