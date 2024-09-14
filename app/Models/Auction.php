<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use SoftDeletes;

    public const TABLE = 'auction';

    public const ID = 'auction_id';
    public const NOTAR_ID = 'auction_notarid';
    public const AUCTION_ID = 'auction_actid';
    public const SOURCE_DELETED = 'auction_source_deleted';
    public const NUMBER = 'auction_number';
    public const CONNECTIONS = 'auction_connections';
    public const DATE = 'auction_date';
    public const TIME = 'auction_time';
    public const TITLE = 'auction_title';
    public const DESC = 'auction_desc';
    public const DESC_FULL = 'auction_desc_full';
    public const BUY_STATUS = 'auction_buy_status';
    public const NOTE = 'auction_note';
    public const PLACE = 'auction_place';
    public const STREET = 'auction_street';
    public const CITY = 'auction_city';
    public const ADDRESS = 'auction_address';
    public const DISTRICT_ID = 'auction_district_id';
    public const REALITY_TYPE = 'auction_reality-type';
    public const COMPANY = 'auction_auction-company';
    public const PROPOSER = 'auction_proposer';
    public const LV = 'auction_lv';
    public const KU = 'auction_ku';
    public const ROUND = 'auction_round';
    public const LISTINA = 'auction_listina';
    public const ARCHIVE = 'auction_archive';
    public const UPDATED = 'auction_updated';

    public const UPDATED_AT = 'auction_update';
    public const CREATED_AT = 'auction_created_at';
    public const DELETED_AT = 'auction_deleted_at';

    public $table = self::TABLE;
    public $primaryKey = self::ID;
    public $timestamps = true;

    protected $casts = [
        self::DATE => 'date'
    ];

    protected $guarded = [self::ID];
}
