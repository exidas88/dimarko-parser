<?php

namespace App\Services;

use App\Enums\Label;
use App\Models\Auction;
use Illuminate\Support\Collection;
use App\Exceptions\DateOutOfRangeException;
use App\Services\Abstracts\AbstractAuctionProcessor;

class NewAuctionProcessor extends AbstractAuctionProcessor
{
    public function __construct(protected string $incomingAuctionId, Collection $details) {
        parent::__construct($incomingAuctionId, $details);
    }

    /**
     * @throws DateOutOfRangeException
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
            Auction::COMPANY => $this->mapper->extract(Label::auctioneer),
            Auction::PROPOSER => $this->mapper->extract(Label::proposer),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::REALITY_TYPE => $this->mapper->extract(Label::subject),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime(),
            Auction::LISTINA => $this->document(),
            Auction::ROUND => 1
        ]);
    }
}
