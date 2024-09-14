<?php

namespace App\Helpers;

class Config
{
    protected const DEFAULT_CONFIG = 'parser';

    public static function months(): int
    {
        return (int)self::get('interval_months');
    }

    public static function get($key, string $file = self::DEFAULT_CONFIG) {
        return config($file . '.' . $key);
    }
}