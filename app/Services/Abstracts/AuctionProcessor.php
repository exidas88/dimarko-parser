<?php

namespace App\Services\Abstracts;

use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Helpers\Config;
use App\Mappers\LabelToAttributeMapper;
use App\Models\Auction;
use App\Repositories\ScheduleRepository;
use App\Services\Interfaces\AuctionProcessorInterface;
use App\Services\NewAuctionProcessor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class AuctionProcessor implements AuctionProcessorInterface
{
    protected Collection $data;
    protected LabelToAttributeMapper $mapper;

    abstract public function run(): void;

    abstract public function setData(): void;

    public function __construct(protected string $auctionId, protected Collection $details)
    {
        $this->mapper = new LabelToAttributeMapper($this->details);

        // Auction id is changed to source actId if needed
        $this->auctionId = $this->resolveAuctionId();
    }

    public function storeData(): void
    {
        Auction::query()->updateOrCreate(
            [Auction::AUCTION_ID => $this->auctionId],
            $this->data->toArray(),
        );
    }

    /**
     * All auction types except the "new" one are supposed to just update the
     * original auction, so we need to resolve the source actId first to be
     * able to update it by the fresh data retrieved from source.
     *
     * @throws ModelNotFoundException
     */
    protected function resolveAuctionId(): string
    {
        // New auctions have source id set automatically
        if ($this instanceof NewAuctionProcessor) {
            return $this->auctionId;
        }

        // Repeated, changed and other types should update its originals
        return ScheduleRepository::sourceAuctionId($this->auctionId);
    }

    /**
     * Get the first document url from the collection of related documents.
     * The collection of documents contains mostly only one item.
     */
    protected function document(): ?string
    {
        $documents = collect($this->mapper->extract(Label::documents));

        if ($documents->isEmpty()) {
            return null;
        }

        return static::sanitizeUrl($documents->first());
    }

    /**
     * Handle the slashes and get URL to the document source.
     */
    protected static function sanitizeUrl(?string $uri): ?string
    {
        $baseUrl = config('parser.source_base_url');

        return rtrim($baseUrl, '/') . '/' . trim($uri, '/');
    }

    /**
     * @throws DateOutOfRangeException
     */
    protected function auctionDate(): string
    {
        $date = explode(' ', $this->mapper->extract(Label::dateTime))[0];

        // Throw exception if auction date is out of interval
        AuctionProcessor::validateDateRange($date);

        return $date;
    }

    protected function auctionTime(): string
    {
        return explode(' ', $this->mapper->extract(Label::dateTime))[1];
    }

    /**
     * @throws DateOutOfRangeException
     */
    public static function validateDateRange(string $date): void
    {
        Carbon::parse($date)->lt(now()->addMonths(Config::months()))
        || throw new DateOutOfRangeException;
    }
}
