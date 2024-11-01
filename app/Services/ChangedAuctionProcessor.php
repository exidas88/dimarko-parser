<?php

namespace App\Services;

use App\Enums\Label;
use App\Models\Auction;
use Illuminate\Support\Collection;
use App\Exceptions\DateOutOfRangeException;
use App\Services\Abstracts\AbstractAuctionProcessor;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChangedAuctionProcessor extends AbstractAuctionProcessor
{
    public function __construct(string $incomingAuctionId, Collection $details) {
        parent::__construct($incomingAuctionId, $details);
    }

    /**
     * @throws DateOutOfRangeException
     * @throws ModelNotFoundException
     */
    public function run(): void
    {
        $this->setData();
        $this->storeData();
    }

    /**
     * @throws DateOutOfRangeException
     */
    public function setData(): void
    {
        $this->data = collect([
            Auction::AUCTION_ID => $this->sourceAuctionId,
            Auction::CONNECTIONS => $this->incomingAuctionId,
            Auction::NUMBER => $this->mapper->extract(Label::number),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime()
        ]);
    }
}
