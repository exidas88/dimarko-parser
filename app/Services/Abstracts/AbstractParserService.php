<?php

namespace App\Services\Abstracts;

use App\Exceptions\ParserException;
use App\Services\Interfaces\HtmlParserInterface;
use App\Services\ParseAuctionDetails;
use App\Services\ParseAuctionsList;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Collection as DomCollection;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\StrictException;

abstract class AbstractParserService implements HtmlParserInterface
{
    protected Dom $dom;
    protected string $url;
    protected array $parameters;

    public function __construct()
    {
        $this->dom = new Dom;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function dom(): Dom
    {
        return $this->dom;
    }

    /**
     * @throws CurlException
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     * @throws LogicalException
     * @throws ParserException
     */
    protected function setDom(): void
    {
        $debug = config('app.debug');

        if ($debug) {
            $this->dom->loadFromFile($this->filePath());
            return;
        }

        $this->url .= '?' . http_build_query($this->parameters);
        $this->dom = $this->dom->loadFromUrl($this->url);
    }

    /**
     * @throws ParserException
     */
    protected function filePath(): string
    {
        $file = match(get_class($this)) {
            ParseAuctionsList::class => 'auctions.html',
            ParseAuctionDetails::class => 'auction-new.html',
            default => throw ParserException::unresolvableFile()
        };

        return public_path($file);
    }

    abstract public function retrieveData(): DomCollection;
}