<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Jobs\ProcessAuctionJob;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProcessAuctionsCmd extends Command
{
    protected const DEFAULT_LIMIT = 5;
    protected const OPTION_TYPE = 'type';
    protected const OPTION_LIMIT = 'limit';

    protected $signature = 'auctions:process {--'.self::OPTION_TYPE.'=} {--'.self::OPTION_LIMIT.'=}';
    protected $description = 'Process scheduled auctions by type';

    public function handle(): void
    {
        $this->query()->map(function (Schedule $schedule) {
            ProcessAuctionJob::dispatch(
                auctionId: $schedule->actId,
                type: $schedule->type
            );
            $schedule->delete();
        });
    }

    protected function query(): Collection
    {
        $type = $this->option(self::OPTION_TYPE);
        $limit = $this->option(self::OPTION_LIMIT) ?? self::DEFAULT_LIMIT;

        return Schedule::query()
            ->when($type, fn(Builder $builder) => $builder
                ->where(Schedule::TYPE, $type)
            )
            ->oldest()
            ->limit($limit)
            ->get();
    }
}
