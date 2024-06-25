<?php

namespace App\Service\ChallengeModeRunData;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Repositories\Interfaces\CombatLog\CombatLogEventRepositoryInterface;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLoggingInterface;
use App\Service\CombatLog\CreateRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ChallengeModeRunDataService implements ChallengeModeRunDataServiceInterface
{
    /** @var Collection<Dungeon> */
    private Collection $dungeonCache;

    public function __construct(
        private readonly CreateRouteDungeonRouteServiceInterface     $createRouteDungeonRouteService,
        private readonly CombatLogEventRepositoryInterface           $combatLogEventRepository,
        private readonly ChallengeModeRunDataServiceLoggingInterface $log
    ) {
        $this->dungeonCache = collect();
    }

    public function convert(bool $force = false, ?callable $onProcess = null): bool
    {
        $result = true;

        try {
            $this->log->convertStart();

            ChallengeModeRunData::when(!$force, function (Builder $builder) {
                $builder->where('processed', false);
            })->chunk(100, function (Collection $rows) use (&$result, $onProcess) {
                /** @var Collection<ChallengeModeRunData> $rows */
                // Stop if there was an error
                if (!$result) {
                    return false;
                }

                foreach ($rows as $row) {
                    $result = $result && $this->convertChallengeModeRunData($row);

                    if ($onProcess !== null) {
                        $onProcess($row);
                    }
                }

                return true;
            });
        } finally {
            $this->log->convertEnd();
        }

        return $result;
    }

    public function convertChallengeModeRunData(ChallengeModeRunData $challengeModeRunData): bool
    {
        $combatLogEventsAttributes = collect();
        try {
            $this->log->convertChallengeModeRunDataStart();

            $decoded = json_decode($challengeModeRunData->post_body, true);

            if (!isset($decoded['challengeMode']['challengeModeId'])) {
                $decoded['challengeMode']['challengeModeId'] =
                    $this->getDungeonFromMapId($decoded['challengeMode']['mapId'])?->challenge_mode_id;
            }

            if ($decoded['challengeMode']['challengeModeId'] === null) {
                $this->log->convertChallengeModeRunDataNoChallengeModeIdSet();

                return true;
            }

            $combatLogEvents = $this->createRouteDungeonRouteService->convertCreateRouteBodyToCombatLogEvents(
                CreateRouteBody::createFromArray($decoded)
            );

            $attributes = [];
            foreach ($combatLogEvents as $combatLogEvent) {
                $attributes[] = $combatLogEvent->getAttributes();
            }

            $result = $this->combatLogEventRepository->insert($attributes)
                && $challengeModeRunData->update(['processed' => true]);
        } finally {
            $this->log->convertChallengeModeRunDataEnd($combatLogEventsAttributes->count());
        }

        return $result;
    }

    public function insertAllToOpensearch(): bool
    {
        return CombatLogEvent::opensearch()
            ->documents()
            ->createAll(null, 1000);
    }

    public function getDungeonFromMapId(int $mapId): ?Dungeon
    {
        if ($this->dungeonCache->isEmpty()) {
            $this->dungeonCache = Dungeon::all()->keyBy('map_id');
        }

        return $this->dungeonCache->get($mapId);
    }
}
