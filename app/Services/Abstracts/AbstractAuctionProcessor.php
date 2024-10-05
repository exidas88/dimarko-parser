<?php

namespace App\Services\Abstracts;

use App\Enums\Label;
use App\Helpers\Config;
use App\Models\Auction;
use App\Services\Logger\LogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Mappers\LabelToAttributeMapper;
use App\Repositories\ScheduleRepository;
use App\Exceptions\DateOutOfRangeException;
use App\Services\Interfaces\AuctionProcessorInterface;

abstract class AbstractAuctionProcessor implements AuctionProcessorInterface
{
    protected Collection $data;
    protected string $sourceAuctionId;
    protected LabelToAttributeMapper $mapper;

    abstract public function run(): void;
    abstract public function setData(): void;

    public function __construct(protected string $incomingAuctionId, protected Collection $details)
    {
        $this->mapper = new LabelToAttributeMapper($this->details);

        // AuctionId is changed to source actId if the processing
        // auction has source_actId defined in Schedule model.
        $this->sourceAuctionId = $this->resolveSourceAuctionId();
    }

    public function storeData(): void
    {
        // Log storing data
        LogService::storingAuctionDetails($this->data);

        // Store Auction data
        Auction::query()->updateOrCreate(
            [Auction::AUCTION_ID => $this->sourceAuctionId],
            $this->data->toArray(),
        );
    }

    /**
     * Repeated, changed and other types should update its originals. If source
     * id isn't set means, the new auction is currently processed, so incoming
     * id is considered as source id. Otherwise, the incoming id is stored to
     * auction_connections to avoid repetitive processing of the same cases.
     */
    protected function resolveSourceAuctionId(): string
    {
        return ScheduleRepository::sourceAuctionId($this->incomingAuctionId) ?? $this->incomingAuctionId;
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
        static::validateDateRange($date);

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
