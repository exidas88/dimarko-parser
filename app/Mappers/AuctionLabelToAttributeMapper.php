<?php

namespace App\Mappers;

use App\Enums\Label;
use App\Models\Auction;
use Illuminate\Support\Collection;

class AuctionLabelToAttributeMapper
{
    public function __construct(protected Collection $details)
    {
        //
    }

    public function extract(Label $label): string
    {
        return $this->details->get($label->name);
    }

    protected array $map = [
        Label::number->name => Auction::NUMBER,
        Label::place->name => Auction::PLACE,
        Label::placeNote->name => Auction::PLACE,
        Label::auctioneer->name => Auction::COMPANY,
        Label::proposer->name => Auction::PROPOSER,
    ];
}