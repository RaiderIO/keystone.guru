<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\WalkGraph\WalkEdgeCollector;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\CombatLog\CombatLogWalkGraphContributionRepositoryInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;

/**
 * Records where players walked in the dungeon: every player's advanced-logging position, turned into walk grid
 * edges per ui map and stored as one contribution per ui map per file. combatlog:buildwalkgraphs aggregates the
 * contributions into walk graphs.
 */
class WalkGraphDataExtractor implements DataExtractorInterface
{
    private const string PLAYER_GUID_PREFIX = 'Player-';

    private WalkEdgeCollector $walkEdgeCollector;

    /** @var array<int, array<int, true>> dungeon id => ui map ids of its floors */
    private array $uiMapIdsByDungeonId = [];

    private ?DataExtractionCurrentDungeon $lastCurrentDungeon = null;

    /** @var array<int, true> the ui map ids of the last current dungeon */
    private array $currentUiMapIds = [];

    public function __construct(
        private readonly CombatLogWalkGraphContributionRepositoryInterface $combatLogWalkGraphContributionRepository,
    ) {
        $this->walkEdgeCollector = new WalkEdgeCollector(
            (float)config('keystoneguru.walk_graph.cell_size'),
            (float)config('keystoneguru.walk_graph.max_step_seconds'),
            (float)config('keystoneguru.walk_graph.max_step_distance'),
            (float)config('keystoneguru.walk_graph.max_speed'),
        );
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->walkEdgeCollector->reset();
        $this->uiMapIdsByDungeonId = [];
        $this->lastCurrentDungeon  = null;
        $this->currentUiMapIds     = [];
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        if (!($parsedEvent instanceof AdvancedCombatLogEvent)) {
            return;
        }

        $advancedData = $parsedEvent->getAdvancedData();
        if (!str_starts_with($advancedData->getInfoGuidRaw(), self::PLAYER_GUID_PREFIX)) {
            return;
        }

        // Positions outside the dungeon's own maps (a city before the key, a ui map nobody mapped) are not ours to graph
        if ($currentDungeon !== $this->lastCurrentDungeon) {
            $this->lastCurrentDungeon = $currentDungeon;
            $this->currentUiMapIds    = $this->getUiMapIds($currentDungeon);
        }

        $uiMapId = $advancedData->getUiMapId();
        $uiMapId = Floor::UI_MAP_ID_MAPPING[$uiMapId] ?? $uiMapId;
        if (!isset($this->currentUiMapIds[$uiMapId])) {
            return;
        }

        $this->walkEdgeCollector->addSample(
            $advancedData->getInfoGuidRaw(),
            $uiMapId,
            $parsedEvent->getTimestamp()->getTimestampMs() / 1000,
            $advancedData->getPositionX(),
            $advancedData->getPositionY(),
        );
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $now     = Carbon::now();
        $runKey  = self::getRunKey($combatLogFilePath);
        $records = [];
        foreach ($this->walkEdgeCollector->getEdgeKeysByUiMapId() as $uiMapId => $edgeKeys) {
            $records[] = [
                'ui_map_id'       => $uiMapId,
                'run_key'         => $runKey,
                'combat_log_path' => $combatLogFilePath,
                'edge_count'      => count($edgeKeys),
                'edges'           => WalkGrid::encodeEdgeKeys($edgeKeys),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        if ($records !== []) {
            $this->combatLogWalkGraphContributionRepository->insert($records);
        }
    }

    /**
     * The files of one Raider.IO run are its segments, named run_<id>_segment_<n> by both the ingestion job and
     * combatlog:downloadruns. Any other file counts as a run of its own.
     */
    public static function getRunKey(string $combatLogFilePath): string
    {
        if (preg_match('/run_(\d+)_segment_\d+/', basename($combatLogFilePath), $matches) === 1) {
            return sprintf('run:%d', $matches[1]);
        }

        return sprintf('file:%s', md5($combatLogFilePath));
    }

    /**
     * @return array<int, true>
     */
    private function getUiMapIds(DataExtractionCurrentDungeon $currentDungeon): array
    {
        $dungeonId = $currentDungeon->dungeon->id;
        if (!isset($this->uiMapIdsByDungeonId[$dungeonId])) {
            $uiMapIds = [];
            foreach ($currentDungeon->dungeon->floors()->whereNotNull('ui_map_id')->pluck('ui_map_id') as $uiMapId) {
                $uiMapIds[(int)$uiMapId] = true;
            }

            $this->uiMapIdsByDungeonId[$dungeonId] = $uiMapIds;
        }

        return $this->uiMapIdsByDungeonId[$dungeonId];
    }
}
