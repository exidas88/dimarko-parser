<?php

namespace App\Jobs;

use Exception;
use App\Enums\AuctionActType;
use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\RequestLimitReachedException;
use App\Repositories\ScheduleRepository;
use App\Services\Abstracts\AuctionProcessor;
use App\Services\ParseAuctionDetails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessAuctionJob implements ShouldQueue
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

        } catch (EmptyDatasetException) {
            ScheduleRepository::delete($this->auctionId); // Auction not found
            dd('Empty dataset');
        } catch (RequestLimitReachedException) {
            dd('Request limit reached');
        } catch (DateOutOfRangeException) {
            dd('Date out of range');
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }
}
