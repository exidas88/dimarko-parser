<?php

namespace App\Repositories;

use App\Enums\AuctionActType;
use App\Models\PageSchedule;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageScheduleRepository
{
    public const DEFAULT_AUCTION_TYPE = AuctionActType::NEW_AUCTION;

    public static function create(?AuctionActType $type = self::DEFAULT_AUCTION_TYPE, ?int $page = 1): PageSchedule
    {
        return PageSchedule::query()->firstOrCreate(
            [PageSchedule::TYPE => $type],
            [PageSchedule::PAGE => $page]
        );
    }

    public static function current(): ?PageSchedule
    {
        try {
            return PageSchedule::query()
                ->whereNotNull(PageSchedule::TYPE)
                ->latest()
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public static function delete(?AuctionActType $type = null): void
    {
        PageSchedule::query()
            ->when($type, fn(Builder $builder) => $builder
                ->where(Schedule::TYPE, $type))
            ->delete();
    }

    public static function currentType(): ?string
    {
        $currentPageSchedule = PageScheduleRepository::current() ?? PageScheduleRepository::create();

        return $currentPageSchedule->type->value;
    }

    public static function moveToNextAuctionType(): void
    {
        $types = AuctionActType::asArray();
        $currentPageSchedule = static::current();

        if (!$currentPageSchedule) {
            static::create();
            return;
        }

        $currentAuctionType = $currentPageSchedule->type;

        foreach($types as $type)
        {
            // Move iterator to the next item
            next($types);

            // If current type is equal to iterated type
            if ($type === $currentAuctionType->value) {
                $current = current($types);

                // Non-empty means there is
                // a next type to go with
                if (!empty($current)) {

                    // Update currently processed schedule
                    $currentPageSchedule->update([
                        PageSchedule::TYPE => $current,
                        PageSchedule::PAGE => 0
                    ]);

                } else {

                    // Otherwise, delete schedules to
                    // let system create the new one
                    PageSchedule::query()->delete();

                }
                break;
            }
        }
    }
}
