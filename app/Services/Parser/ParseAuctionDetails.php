<?php

namespace App\Services\Parser;

use Exception;
use App\Enums\Label;
use App\Enums\Param;
use Illuminate\Support\Str;
use App\Enums\AuctionActType;
use Illuminate\Support\Collection;
use App\Exceptions\EmptyDatasetException;
use App\Exceptions\ParserException;
use App\Exceptions\RequestLimitReachedException;
use App\Services\Abstracts\AbstractParserService;
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
    public function __construct(protected string $auctionId, protected AuctionActType $type)
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

        $parameters = [
            Param::auctionId->value => $this->auctionId,
        ];

        $this->setUrl(config('parser.action_detail_base_url'));
        $this->setParameters($parameters);
        $this->setDomFromUrl();
    }

    /**
     * @return DomCollection
     * @throws ChildNotFoundException
     * @throws EmptyDatasetException
     * @throws NotLoadedException
     * @throws RequestLimitReachedException
     */
    public function retrieveData(): DomCollection
    {
        $sample = $this->dom->find('div.listing')->text;
        $this->validateData($sample);

        $nodes = $this->dom->find('div.listing')->find('p');
        $nodes->count() || throw new EmptyDatasetException;

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
                $value = self::normalizeValue($node->find('span.value'));
                empty($value) || $details->put($label, $value);
            } catch (EmptyCollectionException) {
                //
            }
        });

        return self::mapLabelsToEnum($details);
    }

    /**
     * Method normalizes label values. If value contains anchors,
     * convert it to readable array of links or return plain text.
     */
    protected static function normalizeValue($node): string|array|null
    {
        $anchors = $node->find('a');

        // Convert links to array ['text'=>'uri']
        if ($anchors->count()) {
            $links = [];
            foreach ($anchors as $anchor) {
                $links[trim($anchor->text)] = $anchor->getAttribute('href');
            }
            return $links;
        }

        return trim($node->innerHtml);
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
