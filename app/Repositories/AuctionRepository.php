<?php

namespace App\Repositories;

use App\Enums\AuctionType;
use App\Enums\Label;
use App\Exceptions\UnsupportedAuctionTypeException;
use App\Mappers\AuctionLabelToAttributeMapper;
use App\Services\Abstracts\AbstractAuctionProcessor;
use App\Services\AuctionNewProcessor;
use App\Services\AuctionRepeatProcessor;
use App\Services\AuctionResultProcessor;
use Illuminate\Support\Collection;

class AuctionRepository
{
    /**
     * @throws UnsupportedAuctionTypeException
     */
    public static function process(string $auctionId, Collection $details): void
    {
        $auctionType = self::resolveAuctionTypeFromDetails($details);
        $processorClass = self::resolveProcessorClass($auctionType);

        /** @var AbstractAuctionProcessor $processor */
        $executor = new $processorClass($auctionId, $details);
        $executor->run();
    }

    /**
     * @throws UnsupportedAuctionTypeException
     */
    public static function resolveAuctionTypeFromDetails(Collection $details): AuctionType
    {
        // Extract the auction type value from details
        $mapper = new AuctionLabelToAttributeMapper($details);
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
}