<?php

declare(strict_types=1);

namespace Project\StripFileHeaders;

final class StripResult
{
    public int $scanned;
    public int $changed;

    public function __construct(int $scanned, int $changed)
    {
        $this->scanned = $scanned;
        $this->changed = $changed;
    }
}
