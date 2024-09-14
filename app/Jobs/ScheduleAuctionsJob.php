<?php

namespace App\Jobs;

use App\Exceptions\RequestLimitReachedException;
use App\Repositories\PageScheduleRepository;
use Exception;
use App\Helpers\Config;
use App\Models\PageSchedule;
use App\Enums\AuctionActType;
use App\Repositories\ScheduleRepository;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\UnsetAuctionIdException;
use App\Services\Abstracts\AbstractParserService;
use App\Services\ParseAuctionsList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom\Collection as DomCollection;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

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

            // Dispatch job to parse next page
            //$this->moveToNextPage();

        } catch (EmptyDatasetException) {
            PageScheduleRepository::delete($this->type);
        } catch (RequestLimitReachedException) {
            // Request quota reached
        } catch (Exception $e) {
            Log::error("Error during processing list of auctions: " . $e->getMessage());
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
                ScheduleRepository::create($auctionId, $this->type);
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

    protected function moveToNextPage(): void
    {
        ScheduleAuctionsJob::dispatch($this->type, $this->page);
    }

    protected function logAttributes(): array
    {
        return [
            'page' => $this->page,
            'type' => $this->type->name
        ];
    }
}