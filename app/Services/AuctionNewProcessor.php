<?php

namespace App\Services;

use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Helpers\Config;
use App\Models\Auction;
use App\Services\Abstracts\AuctionProcessor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AuctionNewProcessor extends AuctionProcessor
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
            Auction::NUMBER => $this->mapper->extract(Label::number),
            Auction::COMPANY => $this->mapper->extract(Label::auctioneer),
            Auction::PROPOSER => $this->mapper->extract(Label::proposer),
            Auction::PLACE => $this->mapper->extract(Label::place),
            Auction::DATE => $this->auctionDate(),
            Auction::TIME => $this->auctionTime(),
            Auction::REALITY_TYPE => $this->mapper->extract(Label::subject),
        ]);
    }

    /**
     * @throws DateOutOfRangeException
     */
    protected function auctionDate(): string
    {
        $date = explode(' ', $this->mapper->extract(Label::dateTime))[0];

        // Throw exception if auction date is out of interval
        AuctionProcessor::validateDateRange($date);

        return $date;
    }

    protected function auctionTime(): string
    {
        return explode(' ', $this->mapper->extract(Label::dateTime))[1];
    }
}