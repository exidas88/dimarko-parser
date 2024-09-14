<?php

namespace App\Jobs;

use App\Enums\AuctionActType;
use App\Exceptions\UnsetAuctionIdException;
use Exception;
use App\Services\ParseAuctionsList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;

class AuctionListJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    public function __construct(protected AuctionActType $type, protected int $page, protected ?int $months) {}

    public function handle(): void
    {
        try {

            $parser = new ParseAuctionsList($this->type, $this->page, $this->months);
            $auctions = $parser->retrieveData();

            $auctions->each(function ($tr) use($parser) {
                try {
                    $auctionId = $parser->auctionIdFromRow($tr);
                    AuctionDetailJob::dispatch($auctionId);
                } catch (UnsetAuctionIdException $e) {
                    Log::error($e->getMessage());
                }
            });

        } catch (Exception $e) {
            Log::error($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
            throw $e;
        }
    }
}