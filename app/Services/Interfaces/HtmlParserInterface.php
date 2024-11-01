<?php

namespace App\Services\Interfaces;

use PHPHtmlParser\Dom\Collection as DomCollection;

interface HtmlParserInterface
{
    public function retrieveData(): DomCollection;
    public function validateData(string $text): void;
}