<?php

namespace App\Repositories;

use App\Enums\AuctionActType;
use App\Exceptions\DailyLimitReachedException;
use App\Models\PageSchedule;
use App\Models\Schedule;
use App\Services\Logger\LogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageScheduleRepository
{
    public const DEFAULT_AUCTION_TYPE = AuctionActType::NEW_AUCTION;

    /**
     * @throws DailyLimitReachedException
     */
    public static function create(?AuctionActType $type = self::DEFAULT_AUCTION_TYPE, ?int $page = 0): PageSchedule
    {
        self::validateDailyQuota();

        return PageSchedule::query()->firstOrCreate(
            [PageSchedule::TYPE => $type],
            [PageSchedule::PAGE => $page]
        );
    }

    /**
     * We don't want to let parser work infinitely during all day, so we
     * terminate the process once the daily quota has been reached.
     *
     * @throws DailyLimitReachedException
     */
    protected static function validateDailyQuota(): void
    {
        $dailyLimit = (int)config('parser.cycles_daily_limit');

        if ($dailyLimit) {
            $processedToday = PageSchedule::withTrashed()
                ->where(PageSchedule::DELETED_AT, '>', now()->startOfDay())
                ->count();

            $processedToday < $dailyLimit || throw new DailyLimitReachedException;
        }
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

    /**
     * @throws DailyLimitReachedException
     */
    public static function currentType(): ?string
    {
        $currentPageSchedule = PageScheduleRepository::current() ?? PageScheduleRepository::create();

        return $currentPageSchedule->type->value;
    }

    /**
     * @throws DailyLimitReachedException
     */
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

                    LogService::movedToNextAuctionType($current);

                } else {

                    // Otherwise we reached the last page, so delete
                    // page schedule and start from the beginning
                    PageSchedule::query()->delete();

                    LogService::finishedCycle();

                }
                break;
            }
        }
    }
}
