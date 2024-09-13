<?php

namespace App\Jobs;

use Exception;
use App\Services\ParseAuctionsList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;

class ParseAuctionsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    protected Dom $html;

    public function __construct(protected int $page) {}

    public function handle(): void
    {
        try {
            $parser = new ParseAuctionsList($this->page);
            $auctions = $parser->auctions();

            $auctions->each(function ($tr) use($parser) {
                $auctionId = $parser->auctionIdFromRow($tr);
                ParseAuctionJob::dispatch($auctionId);
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}