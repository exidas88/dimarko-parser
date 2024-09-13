<?php

namespace App\Services;

use App\Enums\Param;
use App\Exceptions\ParserException;
use Exception;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class ParseAuctionDetail extends AbstractParserService
{
    /**
     * @throws Exception
     */
    public function __construct(protected string $auctionId)
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
        $this->setUrl(config('parser.action_detail_base_url'));

        $parameters = [
            Param::auctionId->value => $this->auctionId,
        ];

        $this->setParameters($parameters);
        $this->setDom();
    }

    /**
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     * @throws ParserException
     */
    public function auction(): Collection
    {
        $nodes = $this->dom->find('div.listing')->find('p');
        $nodes->count() || throw ParserException::emptyDataset();

        return $nodes;
    }
}