<?php

namespace App\Service\LiveSession;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\LiveSession\CombatLog\Filters\LiveSessionBufferFilterInterface;
use App\Service\LiveSession\CombatLog\Filters\SummonBufferFilter;
use Illuminate\Support\Collection;
use Throwable;

readonly class LiveSessionCombatLogService implements LiveSessionCombatLogServiceInterface
{
    public function __construct(private CombatLogServiceInterface $combatLogService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function reduceCombatLogForBuffer(string $combatLogFilePath, Collection $validNpcIds): array
    {
        // The route filter is the primary keep signal: everything it acts on (dungeon context, enemy
        // sightings, kills, player deaths, tracked spell casts) is retained verbatim so re-parsing the
        // reduced output reconstructs the same in-flight state. It never creates a DungeonRoute.
        $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();
        $combatLogDungeonRouteFilter->setValidNpcIds($validNpcIds);

        $bufferFilters = $this->makeBufferFilters();

        /** @var array<int, string> $keptLines */
        $keptLines = [];

        try {
            $this->combatLogService->parseCombatLogStreaming(
                $combatLogFilePath,
                function (BaseEvent $baseEvent, int $lineNr) use ($combatLogDungeonRouteFilter, $bufferFilters, &$keptLines): void {
                    $shouldKeep = false;

                    try {
                        $shouldKeep = $combatLogDungeonRouteFilter->parse($baseEvent, $lineNr);
                    } catch (Throwable) {
                        // A single malformed line must not abort the reduction of the whole buffer
                    }

                    if (!$shouldKeep) {
                        foreach ($bufferFilters as $bufferFilter) {
                            if ($bufferFilter->shouldKeep($baseEvent)) {
                                $shouldKeep = true;

                                break;
                            }
                        }
                    }

                    if ($shouldKeep) {
                        $keptLines[] = rtrim($baseEvent->getRawEvent(), "\r\n");
                    }
                },
            );
        } catch (AdvancedLogNotEnabledException) {
            // Advanced logging not (yet) enabled in this batch - nothing to reduce
        }

        return $keptLines;
    }

    /**
     * The extra reasons - beyond auto-route-creator relevance - to retain an event in the reduced buffer.
     * Filters are stateful per reduction pass, so a fresh set is created on every call.
     *
     * Player movement is intentionally NOT retained here: the latest position per player is derived from
     * the freshly ingested lines and persisted to the database every chunk (see
     * {@see \App\Service\LiveSession\LiveSessionBufferProcessingService::processPlayerPositions()}), so
     * keeping a movement trail in the buffer would only bloat it.
     *
     * @return array<int, LiveSessionBufferFilterInterface>
     */
    private function makeBufferFilters(): array
    {
        return [
            new SummonBufferFilter(),
        ];
    }
}
