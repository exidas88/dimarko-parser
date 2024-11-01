<?php
namespace App\Enums;

/**
 * Uncommented cases are automatically searched on external
 * source and processed. Every case needs to have it's
 * representative processor to handle found cases.
 */

enum AuctionActType: string
{
    case NEW_AUCTION = 'new';                       // Oznamenie o drazbe
    case AUCTION_CHANGE_OR_ADDITION = 'changed';    // Zmena v oznameni o drazbe
    case REPEATED_AUCTION = 'repeated';             // Oznamenie o opakovanej drazbe
    case AUCTION_RENOUNCEMENT = 'renouncement';     // Oznamenie o upusteni od drazby

    //case AUCTION_DEFEAT = 'defeat';                 // Oznamenie o zmareni drazby
    //case AUCTION_INVALID = 'invalid';               // Oznamenie o neplatnosti drazby
    //case AUCTION_RESULT = 'result';                 // Oznamenie o vysledku drazby

    public static function asArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
