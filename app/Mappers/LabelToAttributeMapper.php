<?php

namespace App\Mappers;

use App\Enums\Label;
use Illuminate\Support\Collection;

class LabelToAttributeMapper
{
    public function __construct(protected Collection $details) {}

    public function extract(Label $label): string|array|null
    {
        return $this->details->get($label->name);
    }
}
