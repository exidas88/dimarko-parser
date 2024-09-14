<?php

namespace App\Services;

use App\Enums\AuctionActType;
use App\Enums\Param;
use App\Exceptions\ParserException;
use App\Exceptions\UnsetAuctionIdException;
use App\Services\Abstracts\AbstractParserService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom\Collection as DomCollection;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class ParseAuctionsList extends AbstractParserService
{
    protected const AUCTIONS_INTERVAL_MONTHS = 3;
    protected const AUCTIONS_SUBMIT_VALUE = 'Hľadaj';

    /**
     * @throws Exception
     */
    public function __construct(
        protected AuctionActType $type,
        protected int $page,
        protected ?int $months
    ) {
        parent::__construct();
        $this->init();
    }

    /**
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws CurlException
     * @throws ParserException
     * @throws StrictException
     * @throws LogicalException
     */
    public function init(): void
    {
        $this->setUrl(config('parser.action_source_base_url'));
        $months = $this->months ?? self::AUCTIONS_INTERVAL_MONTHS;

        $parameters = [
            Param::start->value => $this->page,
            Param::type->value => $this->type->name,
            Param::dateFrom->value => now()->addMonths($months)->toDateString(),
            Param::submit->value => self::AUCTIONS_SUBMIT_VALUE
        ];

        $this->setParameters($parameters);
        $this->setDom();
    }

    /**
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     * @throws ParserException
     */
    public function retrieveData(): DomCollection
    {
        $list = $this->dom->find('table.search_results')->find('tbody')->find('tr');
        $list->count() || throw ParserException::emptyDataset();

        return $list;
    }

    /**
     * Incoming node represents the row from the auctions list.
     * Returned is the unique auction hash as identifier.
     *
     * @throws UnsetAuctionIdException
     */
    public function auctionIdFromRow($node): string
    {
        $uri = $node->find('td', 0)->find('a')->getAttribute('href');

        return self::retrieveAuctionIdFromUri($uri);
    }

    /**
     * Split URI to separate parameters and extract actId.
     *
     * @throws UnsetAuctionIdException
     */
    protected static function retrieveAuctionIdFromUri(string $uri): string
    {
        $parameters = Str::of($uri)->after('?');
        parse_str($parameters, $queryArray);

        $auctionId = Arr::get($queryArray, 'actId');
        $auctionId || throw new UnsetAuctionIdException;

        return $auctionId;
    }
}