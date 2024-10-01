<?php

namespace App\Services\Abstracts;

use App\Enums\LogType;
use App\Models\ParserLog;

abstract class AbstractLogService
{
    protected static function store(LogType $type, string $message, mixed $data): void
    {
        if (config('app.debug') === false) {
            return;
        }

        $now = now();

        ParserLog::query()->create([
            ParserLog::TYPE => $type,
            ParserLog::MESSAGE => $message,
            ParserLog::DATA => self::resolveRelatedData($data),
            ParserLog::CREATED_AT => $now,
            ParserLog::UPDATED_AT => $now,
        ]);
    }

    protected static function resolveRelatedData(mixed $data): string
    {
        if (is_string($data) || is_numeric($data)) {
            return $data;
        }

        if (is_array($data)) {
            return json_encode($data);
        }

        return serialize($data);
    }
}
