<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\RaiderIOCombatLogWebhookRequest;
use App\Jobs\CombatLog\ProcessCombatLogFanout;
use App\Models\Dungeon;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RaiderIOWebhookController extends Controller
{
    public function combatLog(
        RaiderIOCombatLogWebhookRequest          $request,
        CombatLogParsingCriteriaServiceInterface $criteriaService,
    ): JsonResponse|Response {
        $validated = $request->validated();
        $dungeon   = $request->dungeon();

        $criteria = [new CombatLogParsingCriterionCheck(Dungeon::class, $dungeon->id)];

        $criteria = array_merge(
            $criteria,
            $request->characterClassSpecializations()
                ->map(fn($spec) => new CombatLogParsingCriterionCheck($spec::class, $spec->id))
                ->toArray(),
        );

        $combatLogVersion = (int)$validated['combat_log_version'];

        if ($criteriaService->shouldParse($combatLogVersion, $criteria)) {
            $criteriaService->recordParsed($combatLogVersion, $criteria);

            ProcessCombatLogFanout::dispatch(
                $validated['s3_bucket'],
                $validated['s3_path'],
                $combatLogVersion,
            );

            return response()->json(['message' => 'Accepted'], 202);
        } else {
            return response()->noContent();
        }
    }
}
