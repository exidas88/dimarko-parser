<?php

namespace App\Services;

use App\Enums\Label;
use App\Enums\Param;
use App\Exceptions\ParserException;
use App\Services\Abstracts\AbstractParserService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom\Collection as DomCollection;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class ParseAuctionDetails extends AbstractParserService
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
    public function retrieveData(): DomCollection
    {
        $nodes = $this->dom->find('div.listing')->find('p');
        $nodes->count() || throw ParserException::emptyDataset();

        return $nodes;
    }

    /**
     * Load auction details into a single flatten collection.
     * [label enum => value]
     */
    public function normalizeData(DomCollection $auction): Collection
    {
        $details = collect();

        $auction->each(function ($node) use ($details) {
            try {
                $label = $node->find('span.key')->text;
                $value = $node->find('span.value')->innerHtml;
                empty(trim($value)) || $details->put($label, $value);
            } catch (EmptyCollectionException) {
                //
            }
        });

        return self::mapLabelsToEnum($details);
    }

    /**
     * Transfer auction labels into Enums. The label is removed from
     * collection in case it can't be paired with corresponding Enum.
     */
    protected static function mapLabelsToEnum(Collection $details): Collection
    {
        return $details->mapWithKeys(function ($value, $key) {
            $enum = Label::tryFrom(self::sanitizeLabel($key));
            if ($enum) {
                return [$enum->name => $value];
            }
            return [];
        });
    }

    protected static function sanitizeLabel(string $label): string
    {
        return Str::of($label)->remove([':'])->trim();
    }
}