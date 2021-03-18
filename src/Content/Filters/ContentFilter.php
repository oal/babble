<?php

namespace Babble\Content\Filters;

use Babble\Models\Record;

interface ContentFilter
{
    public function isMatch(Record $record): bool;
}

