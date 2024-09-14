<?php

namespace App\Exceptions;

use Exception;

class UnsetAuctionIdException extends Exception
{
    protected $message = 'Auction id could not be resolved.';
}
