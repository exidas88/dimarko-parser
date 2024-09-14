<?php

namespace App\Console\Commands;

use App\Enums\AuctionActType;
use App\Jobs\AuctionDetailJob;
use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProcessScheduledAuctionsCmd extends Command
{
    protected const DEFAULT_LIMIT = 5;

    protected $signature = 'auctions:process {--type=} {--limit=}';
    protected $description = 'Process scheduled auctions by type';

    public function handle(): void
    {
        $this->query()->map(function (Schedule $schedule) {
            AuctionDetailJob::dispatch(
                auctionId: $schedule->actId,
                type: $schedule->type
            );
            $schedule->delete();
        });
    }

    protected function query(): Collection
    {
        $type = $this->option('type');
        $limit = $this->option('limit') ?? self::DEFAULT_LIMIT;

        return Schedule::query()
            ->when($type, fn(Builder $builder) => $builder
                ->where(Schedule::TYPE, $type)
            )
            ->oldest()
            ->limit($limit)
            ->get();
    }
}
