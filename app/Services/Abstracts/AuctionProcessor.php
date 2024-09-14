<?php

namespace App\Services\Abstracts;

use App\Enums\AuctionType;
use App\Enums\Label;
use App\Exceptions\DateOutOfRangeException;
use App\Exceptions\UnsupportedAuctionTypeException;
use App\Helpers\Config;
use App\Mappers\LabelToAttributeMapper;
use App\Models\Auction;
use App\Services\AuctionNewProcessor;
use App\Services\AuctionRepeatProcessor;
use App\Services\AuctionResultProcessor;
use App\Services\Interfaces\AuctionProcessorInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class AuctionProcessor implements AuctionProcessorInterface
{
    protected Collection $data;
    protected LabelToAttributeMapper $mapper;

    abstract public function run(): void;
    abstract public function setData(): void;

    public function __construct(protected string $auctionId, protected Collection $details) {
        $this->mapper = new LabelToAttributeMapper($this->details);
    }

    public function storeData(): void
    {
        Auction::query()->updateOrCreate(
            [Auction::AUCTION_ID => $this->auctionId],
            $this->data->toArray(),
        );
    }

    /**
     * @throws DateOutOfRangeException
     * @throws UnsupportedAuctionTypeException
     */
    public static function process(string $auctionId, Collection $details): void
    {
        $auctionType = self::resolveAuctionTypeFromDetails($details);
        $processorClass = self::resolveProcessorClass($auctionType);

        /** @var AuctionProcessorInterface $processor */
        $executor = new $processorClass($auctionId, $details);
        $executor->run();
    }

    /**
     * @throws UnsupportedAuctionTypeException
     */
    public static function resolveAuctionTypeFromDetails(Collection $details): AuctionType
    {
        // Extract the auction type value from details
        $mapper = new LabelToAttributeMapper($details);
        $auctionType = $mapper->extract(Label::type);

        // Convert auction type string to enum
        $typeEnum = AuctionType::tryFrom($auctionType);
        $typeEnum || throw new UnsupportedAuctionTypeException;

        return $typeEnum;
    }

    /**
     * Each auction type is processed by it's own processing class.
     *
     * @throws UnsupportedAuctionTypeException
     */
    public static function resolveProcessorClass(AuctionType $auctionType): string
    {
        return match ($auctionType) {
            AuctionType::new => AuctionNewProcessor::class,
            AuctionType::repeated => AuctionRepeatProcessor::class,
            AuctionType::result => AuctionResultProcessor::class,
            default => throw new UnsupportedAuctionTypeException
        };
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