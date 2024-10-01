<?php

namespace App\Services\Logger;

use Throwable;
use App\Enums\LogType;
use App\Services\Abstracts\AbstractLogService;
use PHPHtmlParser\Dom\Collection as DomCollection;

class LogService extends AbstractLogService
{
    public static function retrieveAuctions(array $parameters): void
    {
        $message = 'Requesting URL to retrieve auctions';

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
        $message = 'Thrown through expected exception';

        self::store(type: LogType::debug, message: $message, data: get_class($exception));
    }

    public static function unexpectedErrorException(Throwable $exception): void
    {
        $message = 'Error during processing list of auctions: '.$exception->getMessage();
        $details = ['file' => $exception->getFile(), 'line' => $exception->getLine()];

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

    public static function finishedCycle(): void
    {
        $message = 'Finished cycle';

        self::store(type: LogType::debug, message: $message, data: null);
    }
}
