<?php
namespace App\Helpers;

class Constant
{
    public const INVALID_AUCTION_ID_MESSAGE = 'Neplatné ID';
    public const EMPTY_RESPONSE_MESSAGE = 'Server nevrátil žiadnu odpoveď';
    public const EMPTY_DATASET_MESSAGE = 'Zadaným kritériám nezodpovedajú žiadne výsledky';
    public const LIMIT_REACHED_MESSAGE = 'Prekročili ste povolený počet vyhľadávaní za minútu';

    public static function emptyDatasetResponse(): array
    {
        return [
            self::EMPTY_DATASET_MESSAGE,
            self::EMPTY_RESPONSE_MESSAGE,
            self::INVALID_AUCTION_ID_MESSAGE
        ];
    }
}