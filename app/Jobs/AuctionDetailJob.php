<?php

namespace App\Jobs;

use App\Enums\AuctionActType;
use App\Exceptions\DateOutOfRangeException;
use App\Services\Abstracts\AuctionProcessor;
use Exception;
use App\Repositories\AuctionRepository;
use App\Services\ParseAuctionDetails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AuctionDetailJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    public function __construct(protected string $auctionId, protected AuctionActType $type)
    {
        //
    }

    public function handle(): void
    {
        try {

            $parser = new ParseAuctionDetails($this->auctionId, $this->type);

            $auction = $parser->retrieveData();
            $details = $parser->normalizeData($auction);

            AuctionProcessor::process($this->auctionId, $details);

        } catch (DateOutOfRangeException) {
            // Skip the process if auction is out of range
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
            //throw $e;
        }
    }
}
