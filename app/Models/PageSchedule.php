<?php

namespace App\Models;

use App\Enums\AuctionActType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $page
 * @property AuctionActType $type
 */
class PageSchedule extends Model
{
    use SoftDeletes;

    public const TABLE = 'page_schedules';

    public const ID = 'id';
    public const PAGE = 'page';
    public const TYPE = 'type';

    protected $casts = [
        self::TYPE => AuctionActType::class
    ];

    protected $table = self::TABLE;
    public $timestamps = true;
    protected $primaryKey = self::ID;
    protected $guarded = [self::ID];
}
