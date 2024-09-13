<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\StrictException;

class ParseAuctionsPageJob implements ShouldQueue
{
    use Queueable;

    protected Dom $dom;

    public function __construct(protected int $page)
    {
        $this->dom = new Dom;
    }

    public function handle(): void
    {
        try {
            $this->loadHtml();
            $table = $this->dom->find('table.search_results')[0];
            foreach($table as $content) {
                $body = $content->find('tbody')->find('tr')->first();
                dd($body);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @throws CurlException
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     */
    protected function loadHtml(): Dom
    {
        $baseUrl = config('parser.action_source_base_url');

        $additional = [
            'start' => $this->page,
            'auctionDateTo' => now()->addMonths(3),
            'auction-search' => 'Hľadaj'
        ];

        $baseUrl .= '?' . http_build_query($additional);

        return $this->dom->loadFromUrl($baseUrl);
    }
}
