<?php

namespace App\Repositories;

use App\Models\Auction;

class AuctionRepository
{
    public static function exists(string $actId): bool
    {
        return Auction::query()
            ->where(Auction::AUCTION_ID, $actId)
            ->orWhereJsonContains(Auction::CONNECTIONS, $actId)
            ->exists();
    }
}
