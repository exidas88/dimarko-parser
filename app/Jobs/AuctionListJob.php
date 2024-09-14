<?php

namespace App\Jobs;

use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\EmptyDatasetException;
use App\Helpers\Config;
use App\Repositories\ScheduleRepository;
use Exception;
use App\Enums\AuctionActType;
use App\Exceptions\UnsetAuctionIdException;
use App\Services\Abstracts\AbstractParserService;
use App\Services\ParseAuctionsList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom\Collection as DomCollection;

class AuctionListJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    protected ParseAuctionsList $parser;

    public function __construct(protected AuctionActType $type, protected int $page) {}

    public function handle(): void
    {
        try {

            // Retrieve auction from current page
            $auctions = $this->parser()->retrieveData();

            // Dispatch separate job for each auction
            $this->processAuctions($auctions);

            // Dispatch job to parse next page
            $this->moveToNextPage();

        } catch (EmptyDatasetException) {
            Log::info("Finished with processing auctions: ", $this->logAttributes());
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
                //AuctionDetailJob::dispatch($auctionId, $this->type);
            } catch (UnsetAuctionIdException $e) {
                Log::error($e->getMessage());
            }
        });
    }

    protected function moveToNextPage(): void
    {
        $this->page++;
        AuctionListJob::dispatch($this->type, $this->page);
    }

    protected function logAttributes(): array
    {
        return [
            'page' => $this->page,
            'type' => $this->type->name
        ];
    }
}