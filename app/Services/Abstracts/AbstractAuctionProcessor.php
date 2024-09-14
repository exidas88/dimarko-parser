<?php

namespace App\Services\Abstracts;

use App\Mappers\AuctionLabelToAttributeMapper;
use App\Models\Auction;
use App\Services\Interfaces\AuctionProcessorInterface;
use Illuminate\Support\Collection;

abstract class AbstractAuctionProcessor implements AuctionProcessorInterface
{
    protected Collection $data;
    protected AuctionLabelToAttributeMapper $mapper;

    abstract public function run(): void;
    abstract public function setData(): void;

    public function __construct(protected string $auctionId, protected Collection $details) {
        $this->mapper = new AuctionLabelToAttributeMapper($this->details);
    }

    public function storeData(): void
    {
        Auction::query()->updateOrCreate(
            [Auction::AUCTION_ID => $this->auctionId],
            $this->data->toArray(),
        );
    }
}