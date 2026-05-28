<?php

namespace App\Service\RaiderIO\Dtos;

class SearchAdvancedRunsResponse
{
    /**
     * @param SearchAdvancedRun[] $runs
     */
    public function __construct(
        public readonly array $runs,
        public readonly ?int  $total,
    ) {
    }
}
