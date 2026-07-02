<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogParseFailure                  create(array<string, mixed> $attributes)
 * @method CombatLogParseFailure|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogParseFailure                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogParseFailure                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(CombatLogParseFailure $model)
 * @method bool                                   update(CombatLogParseFailure $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(CombatLogParseFailure $model)
 * @method Collection<int, CombatLogParseFailure> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface CombatLogParseFailureRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Records (or refreshes) a parse failure. Deduplicated on `(run_id, line_number)` so repeated failures
     * for the same run and line update the existing row rather than creating a new one.
     */
    public function recordFailure(
        int     $runId,
        ?int    $seasonId,
        ?int    $combatLogVersion,
        ?int    $lineNumber,
        ?string $rawLine,
        string  $message,
        string  $exceptionClass,
    ): CombatLogParseFailure;
}
