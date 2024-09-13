<?php
namespace App\Enums;

enum Param: string
{
    // list
    case start = 'start';
    case dateFrom = 'auctionDateFrom';
    case dateTo = 'auctionDateTo';
    case type = 'AuctionActType';
    case submit = 'auction-search';

    //detail
    case auctionId = 'actId';
}