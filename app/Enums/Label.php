<?php
namespace App\Enums;

enum Label: string
{
    case notary = 'Označenie notára, ktorý registráciu vykonal';
    case number = 'Spisová značka NCRdr';
    case published = 'Dátum a čas vykonania zápisu v NCRdr';
    case type = 'Registrovaný a uverejnený úkon v NCRdr';
    case auctioneer = 'Dražobník';
    case proposer = 'Navrhovateľ (-ia) dražby';
    case place = 'Miesto konania dražby';
    case placeNote = 'Bližšie označenie miesta konania dražby';
    case dateTime = 'Dátum a čas otvorenia dražby';
    case subject = 'Druh predmetu dražby';
    case relations = 'Súvisiace spisové značky';
    case documents = 'Uverejnené listiny';
}
