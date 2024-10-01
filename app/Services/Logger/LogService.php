<?php

namespace App\Services\Logger;

use Illuminate\Support\Collection;
use Throwable;
use App\Enums\LogType;
use App\Services\Abstracts\AbstractLogService;
use PHPHtmlParser\Dom\Collection as DomCollection;

class LogService extends AbstractLogService
{
    public static function retrieveAuctionsList(array $parameters): void
    {
        $message = 'Requesting URL to retrieve auctions';

        self::store(type: LogType::debug, message: $message, data: $parameters);
    }

    public static function retrieveAuctionDetails(array $parameters): void
    {
        $message = 'Requesting URL to retrieve auction details';

        self::store(type: LogType::debug, message: $message, data: $parameters);
    }

    public static function tryToResolveAuctionId(array $parameters): void
    {
        $message = 'Trying to resolve auction id from uri';

        self::store(type: LogType::debug, message: $message, data: $parameters);
    }

    public static function auctionIdResolvedFromUri(string $auctionId): void
    {
        $message = 'Resolved auction id from uri';

        self::store(type: LogType::debug, message: $message, data: $auctionId);
    }

    public static function foundCasesToProcess(DomCollection $auctions): void
    {
        $message = 'Retrieved list of auctions';

        self::store(type: LogType::debug, message: $message, data: $auctions->count());
    }

    public static function thrownThroughException(Throwable $exception): void
    {
        $message = 'Thrown through exception of type: ' . class_basename($exception);

        self::store(type: LogType::debug, message: $message, data: $exception->getMessage());
    }

    public static function unexpectedErrorException(Throwable $exception): void
    {
        $message = 'Unexpected error exception has occurred: '.$exception->getMessage();
        $details = ['file' => basename($exception->getFile()), 'line' => $exception->getLine()];

        self::store(type: LogType::error, message: $message, data: $details);
    }

    public static function auctionAlreadyExists(string $auctionId): void
    {
        $message = 'Schedule has not been created. Auction already exists.';

        self::store(type: LogType::debug, message: $message, data: $auctionId);
    }

    public static function movedToNextAuctionType(string $type): void
    {
        $message = 'Switched to next auction type';

        self::store(type: LogType::debug, message: $message, data: $type);
    }

    public static function auctionDetailsReadyForProcessing(Collection $data): void
    {
        $message = 'Auction details ready for processing';

        self::store(type: LogType::debug, message: $message, data: $data);
    }

    public static function storingAuctionDetails(Collection $data): void
    {
        $message = 'Storing auction details from resolved data';

        self::store(type: LogType::debug, message: $message, data: $data);
    }

    public static function resolvedSourceAuctionId(string $incomingAuctionId, ?string $sourceAuctionId): void
    {
        $message = "Resolved source id from incoming auction id: $incomingAuctionId";

        self::store(type: LogType::debug, message: $message, data: $sourceAuctionId);
    }

    public static function finishedCycle(): void
    {
        $message = 'Cycle has been finished';

        self::store(type: LogType::debug, message: $message, data: null);
    }
}
