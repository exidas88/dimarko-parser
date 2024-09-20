<?php

namespace App\Jobs;

use App\Enums\AuctionActType;
use App\Exceptions\DailyLimitReachedException;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\RequestLimitReachedException;
use App\Helpers\Config;
use App\Models\PageSchedule;
use App\Repositories\PageScheduleRepository;
use App\Repositories\ScheduleRepository;
use App\Services\Abstracts\AbstractParserService;
use App\Services\Parser\ParseAuctionsList;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom\Collection as DomCollection;

class ScheduleAuctionsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    protected ParseAuctionsList $parser;

    public function __construct(protected AuctionActType $type, protected int $page) {}

    public function handle(): void
    {
        try {
            // Retrieve table of auctions from current page
            $auctions = $this->parser()->retrieveData();

            // Schedule auction ids to be processed
            $this->processAuctions($auctions);

            // Set last processed page in database
            $this->updatePageSchedule();

        } catch (EmptyDatasetException) {
            $this->handleEmptyDatasetException();
        } catch (RequestLimitReachedException) {
            $this->handleRequestLimitReachedException();
        } catch (Exception $e) {
            Log::error("Error during processing list of auctions: " . $e->getMessage(), $this->logAttributes());
        }
    }

    /**
     * @throws Exception
     */
    protected function parser(): AbstractParserService
    {
        $months = Config::months();

        return $this->parser = new ParseAuctionsList($this->type, $this->page, $months);
    }

    protected function processAuctions(DomCollection $auctions): void
    {
        $auctions->each(function ($tr) {
            try {
                $auctionId = $this->parser->auctionIdFromRow($tr);
                $sourceAuctionId = $this->parser->sourceAuctionIdFromRow($tr);
                ScheduleRepository::create($auctionId, $sourceAuctionId, $this->type);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }

    protected function updatePageSchedule(): void
    {
        PageSchedule::query()
            ->where(PageSchedule::TYPE, $this->type)
            ->update([
                PageSchedule::PAGE => $this->page
            ]);
    }

    protected function logAttributes(): array
    {
        return [
            'page' => $this->page,
            'type' => $this->type->name
        ];
    }

    protected function handleEmptyDatasetException(): void
    {
        try {
            PageScheduleRepository::moveToNextAuctionType();
        } catch (DailyLimitReachedException) {
            //
        }
    }

    protected function handleRequestLimitReachedException(): void
    {
        //
    }
}
