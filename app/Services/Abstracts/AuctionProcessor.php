<?php

namespace App\Services\Abstracts;

use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Helpers\Config;
use App\Mappers\LabelToAttributeMapper;
use App\Models\Auction;
use App\Repositories\ScheduleRepository;
use App\Services\Interfaces\AuctionProcessorInterface;
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

        // AuctionId is changed to source actId if the processing
        // auction has source_actId defined in Schedule model.
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
     * Repeated, changed and other types should update its originals. If the source
     * id is not set, the incoming auction id is considered as source id instead.
     *
     * @throws ModelNotFoundException
     */
    protected function resolveAuctionId(): string
    {
        return ScheduleRepository::sourceAuctionId($this->auctionId) ?? $this->auctionId;
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
