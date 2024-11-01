<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParserLog extends Model
{
    public const TABLE = 'parser_logs';
    public const ID = 'id';
    public const TYPE = 'type';
    public const MESSAGE = 'message';
    public const DATA = 'related_data';
    public const ACT_ID = 'related_actid';

    public $timestamps = true;
    protected $guarded = [self::ID];
}
