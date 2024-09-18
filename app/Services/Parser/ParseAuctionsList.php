<?php

namespace App\Services\Parser;

use App\Enums\AuctionActType;
use App\Enums\Param;
use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\ParserException;
use App\Exceptions\RequestLimitReachedException;
use App\Exceptions\UnsetAuctionIdException;
use App\Services\Abstracts\AbstractParserService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom\Collection as DomCollection;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class ParseAuctionsList extends AbstractParserService
{
    protected const AUCTIONS_INTERVAL_MONTHS = 2;
    protected const AUCTIONS_SUBMIT_VALUE = 'Hľadaj';

    /**
     * @throws Exception
     */
    public function __construct(
        protected AuctionActType $type,
        protected int            $page,
        protected ?int           $months
    )
    {
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
            Param::dateFrom->value => now()->toDateString(),
            Param::dateTo->value => now()->addMonths($months)->toDateString(),
            Param::submit->value => self::AUCTIONS_SUBMIT_VALUE
        ];

        $this->setParameters($parameters);
        $this->setDom();
    }

    /**
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     * @throws ParserException
     * @throws EmptyDatasetException
     * @throws DateOutOfRangeException
     * @throws RequestLimitReachedException
     */
    public function retrieveData(): DomCollection
    {
        $list = $this->dom->find('table.search_results')->find('tbody')->find('tr');
        $list->count() || throw new EmptyDatasetException;

        // Get data from the first column, where the
        // potential error messages are rendered
        $sample = $list->find('td', 0)->text;

        $this->validateData($sample);

        return $list;
    }

    /**
     * Incoming node represents the row from the auctions list.
     * Returned is the unique auction hash as its identifier.
     *
     * @throws UnsetAuctionIdException
     * @throws EmptyCollectionException
     */
    public function auctionIdFromRow($node): string
    {
        $uri = $node->find('td', 0)->find('a')->getAttribute('href');

        return self::retrieveAuctionIdFromUri($uri);
    }

    /**
     * When updating the source auction, take id from 4th column,
     * where original case number with the link URI is rendered.
     *
     * @throws UnsetAuctionIdException
     * @throws EmptyCollectionException
     */
    public function sourceAuctionIdFromRow($node): string
    {
        $uri = $node->find('td', 4)->find('a')->getAttribute('href');

        return self::retrieveAuctionIdFromUri($uri);
    }
}
