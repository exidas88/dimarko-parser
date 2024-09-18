<?php

namespace App\Jobs;

use App\Enums\AuctionActType;
use App\Enums\AuctionType;
use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\RequestLimitReachedException;
use App\Exceptions\UnsupportedAuctionTypeException;
use App\Repositories\ScheduleRepository;
use App\Services\ChangedAuctionProcessor;
use App\Services\NewAuctionProcessor;
use App\Services\Parser\ParseAuctionDetails;
use App\Services\RepeatedAuctionProcessor;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
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

            // Parse source and prepare data
            $parser = new ParseAuctionDetails($this->auctionId, $this->type);
            $auction = $parser->retrieveData();
            $details = $parser->normalizeData($auction);

            // Resolve executor and process data
            $class = static::executor($details);
            $executor = new $class($this->auctionId, $details);
            $executor->run();

        } catch (EmptyDatasetException) {
            // Auction not found
            ScheduleRepository::delete($this->auctionId);
            dd('Empty dataset');
        } catch (RequestLimitReachedException) {
            dd('Rate limit reached');
            // Request limit reached
        } catch (DateOutOfRangeException) {
            dd('Date out of range');
            // Auction date out of range
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }

    /**
     * Resolve and execute the class to process the auction.
     *
     * @throws UnsupportedAuctionTypeException
     */
    public static function executor(Collection $details): string
    {
        $auctionType = self::resolveAuctionTypeFromDetails($details);

        return self::executorFromAuctionType($auctionType);
    }

    /**
     * Resolve the auction type from details and return corresponding enum.
     *
     * @throws UnsupportedAuctionTypeException
     */
    public static function resolveAuctionTypeFromDetails(Collection $details): AuctionType
    {
        // Extract the auction type value from details
        $auctionType = $details->get(Label::type->name);

        // Convert auction type to enum
        $typeEnum = AuctionType::tryFrom($auctionType);
        $typeEnum || throw new UnsupportedAuctionTypeException;

        return $typeEnum;
    }

    /**
     * Each auction type is processed by its own processing class.
     *
     * @throws UnsupportedAuctionTypeException
     */
    public static function executorFromAuctionType(AuctionType $auctionType): string
    {
        return match ($auctionType) {
            AuctionType::new => NewAuctionProcessor::class,
            AuctionType::repeated => RepeatedAuctionProcessor::class,
            AuctionType::changed => ChangedAuctionProcessor::class,
            default => throw new UnsupportedAuctionTypeException
        };
    }
}
