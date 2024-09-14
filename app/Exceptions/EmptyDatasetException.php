<?php

namespace App\Exceptions;

use Exception;

class EmptyDatasetException extends Exception
{
    protected $message = 'No data retrieved from source url.';
}
