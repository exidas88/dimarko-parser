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
use App\Services\Abstracts\AbstractAuctionProcessor;
use App\Services\ChangedAuctionProcessor;
use App\Services\Logger\LogService;
use App\Services\NewAuctionProcessor;
use App\Services\Parser\ParseAuctionDetails;
use App\Services\RenouncementAuctionProcessor;
use App\Services\RepeatedAuctionProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Throwable;

class ProcessAuctionJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    public function __construct(protected string $auctionId, protected AuctionActType $type)
    {
        LogService::$auctionId = $this->auctionId;
    }

    public function handle(): void
    {
        try {
            $parser = new ParseAuctionDetails($this->auctionId, $this->type);

            // Parse details and prepare collection
            $auction = $parser->retrieveData();
            $details = $parser->normalizeData($auction);

            // Run executor according to auction type
            $this->executor($details)->run();

        } catch (Throwable $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Resolve and execute the class to process the auction.
     *
     * @throws UnsupportedAuctionTypeException
     */
    protected function executor(Collection $details): AbstractAuctionProcessor
    {
        LogService::auctionDetailsReadyForProcessing($details);

        $auctionType = self::resolveAuctionTypeFromDetails($details);
        $class = self::executorFromAuctionType($auctionType);

        return new $class($this->auctionId, $details);
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
            AuctionType::renouncement => RenouncementAuctionProcessor::class,
            default => throw new UnsupportedAuctionTypeException
        };
    }

    protected function handleException(Throwable $exception): void
    {
        LogService::thrownThroughException($exception);

        match (get_class($exception)) {
            EmptyDatasetException::class => $this->handleEmptyDatasetException(),
            RequestLimitReachedException::class => $this->handleRequestLimitReachedException(),
            DateOutOfRangeException::class => $this->handleDateOutOfRangeException(),
            default => $this->logErrorException($exception)
        };
    }

    protected function handleEmptyDatasetException(): void
    {
        ScheduleRepository::delete($this->auctionId);
    }

    protected function handleRequestLimitReachedException(): void
    {
        //
    }

    protected function handleDateOutOfRangeException(): void
    {
        //
    }

    protected function logErrorException(Throwable $exception): void
    {
        LogService::unexpectedErrorException($exception);
    }
}
