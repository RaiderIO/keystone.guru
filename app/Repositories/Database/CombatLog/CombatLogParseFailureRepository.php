<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogParseFailureRepositoryInterface;

class CombatLogParseFailureRepository extends DatabaseRepository implements CombatLogParseFailureRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogParseFailure::class);
    }

    public function recordFailure(
        int     $runId,
        ?int    $seasonId,
        ?int    $combatLogVersion,
        ?int    $lineNumber,
        ?string $rawLine,
        string  $message,
        string  $exceptionClass,
    ): CombatLogParseFailure {
        return CombatLogParseFailure::updateOrCreate(
            [
                'run_id'      => $runId,
                'line_number' => $lineNumber,
            ],
            [
                'season_id'          => $seasonId,
                'combat_log_version' => $combatLogVersion,
                'raw_line'           => $rawLine,
                'message'            => $message,
                'exception_class'    => $exceptionClass,
                'resolved_at'        => null,
            ],
        );
    }
}
