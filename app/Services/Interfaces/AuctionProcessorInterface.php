<?php

namespace App\Services\Interfaces;

interface AuctionProcessorInterface
{
    public function run(): void;
    public function setData(): void;
}