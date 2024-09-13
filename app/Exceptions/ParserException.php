<?php

namespace App\Exceptions;

use Exception;

class ParserException extends Exception
{
    public static function emptyDataset(): self
    {
        return new self('No results found for searching criteria.');
    }

    public static function unsetAuctionId(): self
    {
        return new self('AuctionId could not be resolved.');
    }

    public static function unresolvableFile(): self
    {
        return new self('Unsupported instance of parser service.');
    }
}
