<?php

namespace App\Services;

use App\Enums\Label;
use App\Models\Auction;
use Illuminate\Support\Collection;
use App\Exceptions\DateOutOfRangeException;
use App\Services\Abstracts\AbstractAuctionProcessor;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RenouncementAuctionProcessor extends AbstractAuctionProcessor
{
    protected const RENOUNCEMENT_NOTE = 'UPUSTENÉ!';

    public function __construct(protected string $incomingAuctionId, Collection $details) {
        parent::__construct($this->incomingAuctionId, $details);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function run(): void
    {
        $this->setData();
        $this->storeData();
    }

    public function setData(): void
    {
        $this->data = collect([
            Auction::AUCTION_ID => $this->sourceAuctionId,
            Auction::CONNECTIONS => $this->incomingAuctionId,
            Auction::NOTE => self::RENOUNCEMENT_NOTE
        ]);
    }
}
