<?php

namespace App\Jobs;

use Exception;
use App\Services\ParseAuctionDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;

class ParseAuctionJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    protected Dom $html;

    public function __construct(protected string $auctionId)
    {
        //
    }

    public function handle(): void
    {
        try {
            $parser = new ParseAuctionDetail($this->auctionId);
            $auction = $parser->auction();

            $auction->each(function ($node) use($parser) {
                dd($node->innerHtml);
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
