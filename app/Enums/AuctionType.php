<?php
namespace App\Enums;

enum AuctionType: string
{
    case new = 'Oznámenie o dražbe';
    case repeated = 'Oznámenie o konaní opakovanej dražby';
    case changed = 'Zmena v oznámení o dražbe / Dodatok';
    case renouncement = 'Oznámenie o upustení od dražby';
    case result = 'Oznámenie o výsledku dražby';
}
