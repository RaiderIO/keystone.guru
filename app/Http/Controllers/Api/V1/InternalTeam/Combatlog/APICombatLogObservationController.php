<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\Observation\CombatLogObservationDensityRequest;
use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcCharacteristicObservationRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellPropertyObservationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class APICombatLogObservationController extends Controller
{
    /**
     * The default response includes the full per-tuple list only up to this many tuples.
     */
    private const int TUPLE_CAP = 200;

    /**
     * Even with `?detailed=1`, the per-tuple list never exceeds this many tuples.
     */
    private const int DETAILED_TUPLE_CAP = 2000;

    /**
     * @OA\Get(
     *     operationId="getCombatLogObservationDensity",
     *     path="/api/v1/combatlog/observations/density",
     *     summary="Distinct observed_on-day density per (spell_id, property) and (npc_id, characteristic_id) tuple, across both observation tables - the #4356 step-2 measurement",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="detailed", in="query", required=false, description="Include the raw per-tuple list even above the default cap (still bounded by a larger cap)", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Not an admin or ai_agent"),
     * )
     */
    public function density(
        CombatLogObservationDensityRequest                       $request,
        CombatLogSpellPropertyObservationRepositoryInterface     $spellPropertyObservationRepository,
        CombatLogNpcCharacteristicObservationRepositoryInterface $npcCharacteristicObservationRepository,
    ): JsonResponse {
        $detailed = $request->isDetailed();

        /** @var Collection<int, array<string, mixed>> $spellTuples */
        $spellTuples = $spellPropertyObservationRepository->getTupleDensity()
            ->map(static fn(CombatLogSpellPropertyObservation $row): array => [
                'spell_id'      => $row->spell_id,
                'property'      => $row->property->value,
                'days_observed' => (int)$row->getAttribute('days_observed'),
            ]);

        /** @var Collection<int, array<string, mixed>> $npcTuples */
        $npcTuples = $npcCharacteristicObservationRepository->getTupleDensity()
            ->map(static fn(CombatLogNpcCharacteristicObservation $row): array => [
                'npc_id'            => $row->npc_id,
                'characteristic_id' => $row->characteristic_id,
                'days_observed'     => (int)$row->getAttribute('days_observed'),
            ]);

        return response()->json([
            'data' => [
                'spell_property_observations' => $this->buildTableDensity(
                    $spellPropertyObservationRepository->countAll(),
                    $spellPropertyObservationRepository->getObservedOnDateRange(),
                    $spellTuples,
                    $detailed,
                ),
                'npc_characteristic_observations' => $this->buildTableDensity(
                    $npcCharacteristicObservationRepository->countAll(),
                    $npcCharacteristicObservationRepository->getObservedOnDateRange(),
                    $npcTuples,
                    $detailed,
                ),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     operationId="getCombatLogSpellObservationHistory",
     *     path="/api/v1/combatlog/observations/spells/{spell}",
     *     summary="Per-property observed_on history for one spell - the per-event debugging tool",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="spell", in="path", required=true, description="Spell id", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Not an admin or ai_agent"),
     *     @OA\Response(response=404, description="Unknown spell"),
     * )
     */
    public function spellHistory(
        Spell                                                $spell,
        CombatLogSpellPropertyObservationRepositoryInterface $spellPropertyObservationRepository,
    ): JsonResponse {
        $properties = $spellPropertyObservationRepository->getHistoryForSpell($spell->id)
            ->map(static fn(Collection $observedOn): array => $observedOn
                ->map(static fn(Carbon $date): string => $date->toDateString())
                ->all())
            ->all();

        return response()->json([
            'data' => [
                'spell_id'   => $spell->id,
                'properties' => empty($properties) ? (object)[] : $properties,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     operationId="getCombatLogNpcObservationHistory",
     *     path="/api/v1/combatlog/observations/npcs/{npc}",
     *     summary="Per-characteristic observed_on history for one NPC - the per-event debugging tool",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="npc", in="path", required=true, description="Npc id", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(type="object")),
     *     @OA\Response(response=403, description="Not an admin or ai_agent"),
     *     @OA\Response(response=404, description="Unknown NPC"),
     * )
     */
    public function npcHistory(
        Npc                                                      $npc,
        CombatLogNpcCharacteristicObservationRepositoryInterface $npcCharacteristicObservationRepository,
    ): JsonResponse {
        $history = $npcCharacteristicObservationRepository->getHistoryForNpc($npc->id);

        /** @var Collection<int, Characteristic> $characteristicsById */
        $characteristicsById = $history->isEmpty()
            ? new Collection()
            : Characteristic::query()->whereIn('id', $history->keys())->get()->keyBy('id');

        $characteristics = $history
            ->mapWithKeys(function (Collection $observedOn, int $characteristicId) use ($characteristicsById): array {
                /** @var Characteristic|null $characteristic */
                $characteristic = $characteristicsById->get($characteristicId);

                return [
                    (string)$characteristicId => [
                        'characteristic_id' => $characteristicId,
                        'key'               => $characteristic?->key,
                        'observed_on'       => $observedOn
                            ->map(static fn(Carbon $date): string => $date->toDateString())
                            ->all(),
                    ],
                ];
            })
            ->all();

        return response()->json([
            'data' => [
                'npc_id'          => $npc->id,
                'characteristics' => empty($characteristics) ? (object)[] : $characteristics,
            ],
        ]);
    }

    /**
     * @param array{min: ?Carbon, max: ?Carbon, count: int} $observedOnRange
     * @param Collection<int, array<string, mixed>>         $tuples
     *
     * @return array<string, mixed>
     */
    private function buildTableDensity(int $rowCount, array $observedOnRange, Collection $tuples, bool $detailed): array
    {
        $histogram = $tuples
            ->groupBy('days_observed')
            ->map(static fn(Collection $group): int => $group->count())
            ->sortKeys()
            ->all();

        $tupleCount    = $tuples->count();
        $includeTuples = $tupleCount <= self::TUPLE_CAP || $detailed;
        $cappedTuples  = $includeTuples ? $tuples->take(self::DETAILED_TUPLE_CAP) : new Collection();

        return [
            'row_count'   => $rowCount,
            'observed_on' => [
                'min'   => $observedOnRange['min']?->toDateString(),
                'max'   => $observedOnRange['max']?->toDateString(),
                'count' => $observedOnRange['count'],
            ],
            'histogram' => empty($histogram) ? (object)[] : $histogram,
            'tuples'    => $includeTuples ? $cappedTuples->values()->all() : null,
            'truncated' => $includeTuples && $cappedTuples->count() < $tupleCount,
        ];
    }
}
