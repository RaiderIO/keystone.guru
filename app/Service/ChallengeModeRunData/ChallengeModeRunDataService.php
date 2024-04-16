<?php

namespace App\Service\ChallengeModeRunData;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Service\ChallengeModeRunData\Logging\ChallengeModeRunDataServiceLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChallengeModeRunDataService implements ChallengeModeRunDataServiceInterface
{
    /** @var Collection<Dungeon> */
    private Collection $dungeonCache;

    public function __construct(
        private readonly ChallengeModeRunDataServiceLoggingInterface $log
    ) {
        $this->dungeonCache = collect();
    }

    public function convert(): bool
    {
        $result = true;

        try {
            $this->log->convertStart();

            ChallengeModeRunData::chunk(100, function (Collection $rows) use (&$result) {
                // Stop if there was an error
                if (!$result) {
                    return false;
                }

                foreach ($rows as $row) {
                    $result = $result && $this->convertChallengeModeRunData($row);
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
        $result = true;

        $combatLogEventsAttributes = collect();
        try {
            $this->log->convertChallengeModeRunDataStart();

            $decoded = json_decode($challengeModeRunData->post_body, true);

            $start = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $decoded['challengeMode']['start'])->getTimestampMs();
            $end   = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $decoded['challengeMode']['end'])->getTimestampMs();

            $dungeon = $this->getDungeonFromMapId($decoded['challengeMode']['mapId']);

            if ($dungeon === null) {
                $this->log->convertChallengeModeRunDataUnableToFindDungeon($decoded['challengeMode']['mapId']);

                return false;
            }

            $defaultAttributes = [
                'run_id'            => $decoded['metadata']['runId'],
                'challenge_mode_id' => $dungeon->challenge_mode_id,
                'level'             => $decoded['challengeMode']['level'],
                'affix_ids'         => json_encode($decoded['challengeMode']['affixes']),
                'success'           => $decoded['challengeMode']['durationMs'] < $dungeon->currentMappingVersion->timer_max_seconds * 1000,
                'start'             => $start,
                'end'               => $end,
                'duration_ms'       => $decoded['challengeMode']['durationMs'],
                'characters'        => json_encode([]),
                'context'           => json_encode([]),
                'created_at'        => $start,
            ];

            $this->log->convertChallengeModeRunDefaultAttributes($defaultAttributes);

            foreach ($decoded['npcs'] as $killedNpc) {
                $combatLogEventsAttributes->push(array_merge($defaultAttributes, [
                    'event_type' => CombatLogEvent::EVENT_TYPE_ENEMY_KILLED,
                    'pos_x'      => $killedNpc['coord']['x'],
                    'pos_y'      => $killedNpc['coord']['y'],
                    'ui_map_id'  => $killedNpc['coord']['ui_map_id'],
                ]));
            }

            $result = CombatLogEvent::insert($combatLogEventsAttributes->toArray());
        } finally {
            $this->log->convertChallengeModeRunDataEnd($combatLogEventsAttributes->count());
        }

        return $result;
    }

    public function getDungeonFromMapId(int $mapId): ?Dungeon
    {
        if ($this->dungeonCache->isEmpty()) {
            $this->dungeonCache = Dungeon::all()->keyBy('map_id');
        }

        return $this->dungeonCache->get($mapId);
    }
}
