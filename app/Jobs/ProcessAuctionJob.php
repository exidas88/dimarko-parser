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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        } catch (Throwable $exception) {
            $this->handleException($exception);
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

    protected function handleException(Throwable $exception): void
    {
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
        Log::error('Error actId [' . $this->auctionId . '] of type [' . $this->type->value . ']: ' . $exception->getMessage());
    }
}
