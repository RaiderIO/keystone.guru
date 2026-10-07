<?php

namespace Tests\Fixtures\Traits;

use ArrayObject;
use Tests\Fixtures\ParseCountingCombatLogService;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesParseCountingCombatLogService
{
    /**
     * @param ArrayObject<int, string> $parsedFilePaths
     */
    private function createParseCountingCombatLogService(ArrayObject $parsedFilePaths): ParseCountingCombatLogService
    {
        return $this->app->make(ParseCountingCombatLogService::class, ['parsedFilePaths' => $parsedFilePaths]);
    }
}
