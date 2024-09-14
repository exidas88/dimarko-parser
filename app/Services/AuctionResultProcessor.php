<?php

namespace App\Services;

use App\Services\Abstracts\AuctionProcessor;
use Illuminate\Support\Collection;

class AuctionResultProcessor extends AuctionProcessor
{
    public function __construct(string $auctionId, Collection $details) {
        parent::__construct($auctionId, $details);
    }

    public function run(): void
    {
        // TODO: Implement execute() method.
    }

    public function setData(): void
    {
        // TODO: Implement setData() method.
    }
}