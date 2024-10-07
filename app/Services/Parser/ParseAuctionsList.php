<?php

namespace App\Services\Parser;

use Exception;
use App\Enums\Param;
use App\Enums\AuctionActType;
use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\ParserException;
use App\Exceptions\RequestLimitReachedException;
use App\Exceptions\UnsetAuctionIdException;
use App\Services\Abstracts\AbstractParserService;
use Illuminate\Support\Facades\Log;
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
    protected const AUCTIONS_ROWS_PER_PAGE = 20;
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
        if (static::parseFromFile()) {
            $this->setDomFromFile();
            return;
        }

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
        $this->setDomFromUrl();
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
        $rows = $this->dom->find('table.search_results')->find('tbody')->find('tr');
        $rows->count() || throw new EmptyDatasetException;

        // Get data from the first column, where the
        // potential error messages are rendered
        $sample = $rows->find('td', 0)->text;
        $this->validateData($sample);

        // Remove paging row from the collection
        if ($this->page > 1 || $rows->count() > self::AUCTIONS_ROWS_PER_PAGE) {
            $pagingOffset = $rows->count() - 1;
            $rows->offsetUnset($pagingOffset);
        }

        return $rows;
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
    public function sourceAuctionIdFromRow($node): ?string
    {
        try {

            $td = $node->find('td', 4);
            $anchor = $td->find('a');

            if (empty(trim($anchor->text))) {
                return null;
            }

            $uri = $anchor->getAttribute('href');

            return self::retrieveAuctionIdFromUri($uri);

        } catch (Exception) {

            // If source auction id is not set when processing repeated or changed auction,
            // we cannot continue, because we need original case that needs to be updated
            in_array($this->type, [AuctionActType::REPEATED_AUCTION, AuctionActType::AUCTION_CHANGE_OR_ADDITION])
            && throw new UnsetAuctionIdException;

        }

        return null;
    }
}
