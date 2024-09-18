<?php

namespace App\Services;

use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Helpers\Config;
use App\Models\Auction;
use App\Services\Abstracts\AbstractParserService;
use App\Services\Abstracts\AuctionProcessor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NewAuctionProcessor extends AuctionProcessor
{
    public function __construct(string $auctionId, Collection $details) {
        parent::__construct($auctionId, $details);
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
            Auction::AUCTION_ID => $this->auctionId,
            Auction::CONNECTIONS => $this->auctionId,
            Auction::NUMBER => $this->mapper->extract(Label::number),
            Auction::COMPANY => $this->mapper->extract(Label::auctioneer),
            Auction::PROPOSER => $this->mapper->extract(Label::proposer),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::REALITY_TYPE => $this->mapper->extract(Label::subject),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime(),
            Auction::LISTINA => $this->document(),
        ]);
    }
}
