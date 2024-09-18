<?php

namespace App\Services;

use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Models\Auction;
use App\Services\Abstracts\AuctionProcessor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class RepeatedAuctionProcessor extends AuctionProcessor
{
    public function __construct(string $auctionId, Collection $details) {
        parent::__construct($auctionId, $details);
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
            Auction::AUCTION_ID => $this->auctionId,
            Auction::CONNECTIONS => $this->auctionId,
            Auction::NUMBER => $this->mapper->extract(Label::number),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime(),
            Auction::LISTINA => $this->document(),
        ]);
    }
}
