<?php

namespace App\Models;

use App\Enums\AuctionActType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $actId
 * @property string $source_actId
 * @property AuctionActType $type
 */
class Schedule extends Model
{
    use SoftDeletes;

    public const ID = 'id';
    public const ACT_ID = 'actId';
    public const SOURCE_ACT_ID = 'source_actId';
    public const TYPE = 'type';

    protected $casts = [
        self::TYPE => AuctionActType::class
    ];

    public $timestamps = true;
    protected $primaryKey = self::ID;
    protected $guarded = [self::ID];
}
