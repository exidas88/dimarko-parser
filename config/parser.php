<?php
use App\Enums\Source;

return [
    'debug' => env('PARSER_DEBUG', true),
    'source' => env('PARSER_SOURCE', Source::web->value),
    'interval_months' => env('PARSER_INTERVAL_MONTHS', 3),
    'cycles_daily_limit' => env('PARSER_CYCLES_DAILY_LIMIT'),
    'purge_logs_months' => env('PARSER_PURGE_LOGS_MONTHS', 3),
    'source_base_url' => 'https://www.notar.sk/',
    'action_source_base_url' => 'https://www.notar.sk/drazby/',
    'action_detail_base_url' => 'https://www.notar.sk/drazba/',
    'files' => [
        'list' => public_path('auctions.html'),
        'new' => public_path('auction-new.html'),
        'repeated' => public_path('auction-repeated.html'),
        'result' => public_path('auction-result.html'),
        'renouncement' => public_path('auction-renouncement.html'),
    ]
];
