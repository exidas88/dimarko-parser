<?php

namespace App\Services;

use App\Enums\Label;
use App\Models\Auction;
use App\Services\Abstracts\AbstractAuctionProcessor;
use Illuminate\Support\Collection;

class AuctionNewProcessor extends AbstractAuctionProcessor
{
    public function __construct(string $auctionId, Collection $details) {
        parent::__construct($auctionId, $details);
    }

    public function run(): void
    {
        $this->setData();
        $this->storeData();
    }

    public function setData(): void
    {
        $this->data = collect([
            Auction::AUCTION_ID => $this->auctionId,
            Auction::NUMBER => $this->mapper->extract(Label::number),
            Auction::COMPANY => $this->mapper->extract(Label::auctioneer),
            Auction::PROPOSER => $this->mapper->extract(Label::proposer),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime(),
            Auction::REALITY_TYPE => $this->mapper->extract(Label::subject),
        ]);
    }

    protected function auctionDate(): string
    {
        return explode(' ', $this->mapper->extract(Label::dateTime))[0];
    }

    protected function auctionTime(): string
    {
        return explode(' ', $this->mapper->extract(Label::dateTime))[1];
    }
}