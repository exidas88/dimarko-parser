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
    public function __construct(protected string $incomingAuctionId, Collection $details) {
        parent::__construct($this->incomingAuctionId, $details);
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
            Auction::TIME => $this->auctionTime(),
            Auction::LISTINA => $this->document(),
        ]);
    }

    public function storeData(): void
    {
        parent::storeData();

        Auction::query()
            ->where(Auction::AUCTION_ID, $this->sourceAuctionId)
            ->increment(Auction::ROUND);
    }
}
