<?php

namespace App\Services\Abstracts;

use App\Enums\Source;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\ParserException;
use App\Exceptions\RequestLimitReachedException;
use App\Exceptions\UnsetAuctionIdException;
use App\Helpers\Constant;
use App\Services\Interfaces\HtmlParserInterface;
use App\Services\Logger\LogService;
use App\Services\Parser\ParseAuctionDetails;
use App\Services\Parser\ParseAuctionsList;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    /**
     * @throws CurlException
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     */
    protected function setDomFromUrl(): void
    {
        // Set request URL
        $this->url .= '?' . http_build_query($this->parameters);

        // Log request URL
        match(get_class($this)) {
            ParseAuctionsList::class => LogService::retrieveAuctionsList($this->parameters),
            ParseAuctionDetails::class => LogService::retrieveAuctionDetails($this->parameters),
            default => null
        };

        // Retrieve data and set DOM
        $this->dom = $this->dom->loadFromUrl($this->url);
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     * @throws LogicalException
     * @throws ParserException
     */
    protected function setDomFromFile(): void
    {
        $this->dom->loadFromFile($this->resolveFile());
    }

    /**
     * @throws ParserException
     */
    protected function resolveFile(): string
    {
        return match(get_class($this)) {
            ParseAuctionsList::class => config('parser.files.list'),
            ParseAuctionDetails::class => $this->resolveFileFromAuctionType(),
            default => throw ParserException::unresolvableFile()
        };
    }

    protected function resolveFileFromAuctionType(): string
    {
        return Arr::get(config('parser.files'), $this->type->value);
    }

    /**
     * @throws RequestLimitReachedException
     * @throws EmptyDatasetException
     */
    public function validateData(string $text): void
    {
        Str::of($text)->contains(Constant::emptyDatasetResponse())
        && throw new EmptyDatasetException;

        Str::of($text)->contains(Constant::LIMIT_REACHED_MESSAGE)
        && throw new RequestLimitReachedException;
    }

    /**
     * Split URI to separate parameters and extract actId.
     *
     * @throws UnsetAuctionIdException
     */
    public static function retrieveAuctionIdFromUri(string $uri): string
    {
        $parameters = Str::of($uri)->after('?');
        parse_str($parameters, $queryArray);

        $auctionId = Arr::get($queryArray, 'actId');
        $auctionId || throw new UnsetAuctionIdException;

        return $auctionId;
    }

    public static function parseFromFile(): bool
    {
        return config('parser.source') === Source::file->value;
    }

    abstract public function retrieveData(): DomCollection;
}
