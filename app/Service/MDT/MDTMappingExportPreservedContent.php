<?php

namespace App\Service\MDT;

use App\Service\MDT\Exceptions\LuaParseException;
use App\Service\MDT\Lua\LuaLiteral;
use App\Service\MDT\Lua\LuaTableParser;

/**
 * Reads an existing MDT dungeon file and hands back the parts of it that MDT owns and we cannot
 * reproduce from our own mapping.
 *
 * Three categories of content live here:
 *
 * - Data we deliberately do not export. NPC spells are the NPC Compendium's data, fed by a rolling
 *   window of combat log parses, and are not handed to MDT.
 * - Data we simply do not have. `teleportId`/`iconId` have no column, MDT's hand written `L[...]`
 *   translation keys are inconsistent per dungeon (`L["Kings' Rest"]` next to `L["MurderRow"]`) and
 *   index into MDT's own locale files, and POI types such as `genericItem` are dropped on import.
 * - Coordinates that only differ because of precision loss. `enemies`.`lat`/`lng` are `double(8,2)`,
 *   so MDT's ~14 significant digits cannot survive a round trip. Where a freshly calculated
 *   coordinate still lands on MDT's original within {@see self::COORDINATE_TOLERANCE}, the original
 *   is kept - the enemy did not move, we just cannot say where it is that precisely.
 */
class MDTMappingExportPreservedContent
{
    /**
     * The largest distance, in MDT coordinates, at which a calculated coordinate is still considered
     * to be the same position as MDT's. Our storage precision alone accounts for up to 0.011
     * (0.005 lat/lng * 2.185), so anything under this is precision loss rather than a moved enemy.
     */
    private const float COORDINATE_TOLERANCE = 0.05;

    /**
     * @param array<string, mixed> $assignments
     */
    private function __construct(private readonly array $assignments)
    {
    }

    /**
     * @throws LuaParseException
     */
    public static function fromFile(string $filePath): self
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new LuaParseException(sprintf('Unable to read %s', $filePath));
        }

        return new self(new LuaTableParser($contents)->parseDungeonIndexAssignments());
    }

    /**
     * @param array<string, mixed> $assignments
     */
    public static function fromParsedAssignments(array $assignments): self
    {
        return new self($assignments);
    }

    /**
     * @return list<string> The order MDT lists its `MDT.<name>[dungeonIndex]` assignments in. It is not
     *                      the same in every file, and re-ordering them would show up as a diff.
     */
    public function getSectionOrder(): array
    {
        return array_keys($this->assignments);
    }

    /**
     * @return LuaLiteral|null The `MDT.dungeonList[dungeonIndex]` value, e.g. `L["Kings' Rest"]`.
     */
    public function getDungeonListValue(): ?LuaLiteral
    {
        $value = $this->assignments['dungeonList'] ?? null;

        return $value instanceof LuaLiteral ? $value : null;
    }

    /**
     * @return LuaLiteral|null A single value out of `MDT.mapInfo[dungeonIndex]`, e.g. `teleportId`.
     */
    public function getMapInfoValue(string $key): ?LuaLiteral
    {
        $mapInfo = $this->assignments['mapInfo'] ?? null;
        if (!is_array($mapInfo)) {
            return null;
        }

        $value = $mapInfo[$key] ?? null;

        return $value instanceof LuaLiteral ? $value : null;
    }

    /**
     * @return LuaLiteral|null The `MDT.dungeonSubLevels[dungeonIndex]` entry at $index.
     */
    public function getSubLevelValue(int $index): ?LuaLiteral
    {
        $subLevels = $this->assignments['dungeonSubLevels'] ?? null;
        if (!is_array($subLevels)) {
            return null;
        }

        $value = $subLevels[$index] ?? null;

        return $value instanceof LuaLiteral ? $value : null;
    }

    /**
     * @return int|null The index MDT lists this NPC at, null if it does not know it. Keeping MDT's order means
     *                  a newly added NPC lands at the end instead of shifting every index after it.
     */
    public function getEnemyIndex(int $npcId): ?int
    {
        $enemies = $this->assignments['dungeonEnemies'] ?? null;
        if (!is_array($enemies)) {
            return null;
        }

        foreach ($enemies as $index => $enemy) {
            if (!is_array($enemy)) {
                continue;
            }

            $id = $enemy['id'] ?? null;
            if ($id instanceof LuaLiteral && $id->toInt() === $npcId) {
                return is_int($index) ? $index : null;
            }
        }

        return null;
    }

    /**
     * @return LuaLiteral|array<int|string, mixed>|null A single value MDT has on this NPC, null if it has none.
     */
    public function getEnemyValue(int $npcId, string $key): LuaLiteral|array|null
    {
        $enemy = $this->findEnemyByNpcId($npcId);
        if ($enemy === null) {
            return null;
        }

        $value = $enemy[$key] ?? null;

        return $value === [] ? null : $value;
    }

    /**
     * Keeps MDT's original coordinate when our freshly calculated one lands on the same spot.
     *
     * Clones are matched on position rather than on index: adding or removing a single enemy shifts every
     * index after it, which would otherwise rewrite the whole NPC's coordinates for no reason.
     *
     * @param  float                         $x The calculated MDT x coordinate, unrounded.
     * @param  float                         $y The calculated MDT y coordinate, unrounded.
     * @return array<int|string, mixed>|null MDT's clone for this enemy, null when it moved or is new.
     */
    public function getMatchingClone(int $npcId, float $x, float $y): ?array
    {
        $enemy = $this->findEnemyByNpcId($npcId);
        if ($enemy === null) {
            return null;
        }

        $clones = $enemy['clones'] ?? null;
        if (!is_array($clones)) {
            return null;
        }

        foreach ($clones as $clone) {
            if (is_array($clone) && $this->matchesCoordinate($clone, $x, $y)) {
                return $clone;
            }
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>|null MDT's clone at this index, regardless of where it sits.
     *                                       Used to carry a moved enemy's patrol and notes over - only its
     *                                       coordinates changed, everything hanging off it is still MDT's.
     */
    public function getCloneByIndex(int $npcId, int $cloneIndex): ?array
    {
        $enemy = $this->findEnemyByNpcId($npcId);
        if ($enemy === null) {
            return null;
        }

        $clones = $enemy['clones'] ?? null;
        if (!is_array($clones)) {
            return null;
        }

        $clone = $clones[$cloneIndex] ?? null;

        return is_array($clone) ? $clone : null;
    }

    /**
     * @return array<int, mixed>|null MDT's entire `MDT.mapPOIs[dungeonIndex]` table. An empty array means MDT
     *                                deliberately has no POIs here; null means the file has no such table.
     */
    public function getMapPOIs(): ?array
    {
        $mapPOIs = $this->assignments['mapPOIs'] ?? null;

        return is_array($mapPOIs) ? $mapPOIs : null;
    }

    /**
     * @param array<int|string, mixed> $target
     */
    private function matchesCoordinate(array $target, float $x, float $y): bool
    {
        $targetX = $target['x'] ?? null;
        $targetY = $target['y'] ?? null;

        if (!$targetX instanceof LuaLiteral || !$targetY instanceof LuaLiteral) {
            return false;
        }

        return abs($targetX->toFloat() - $x) <= self::COORDINATE_TOLERANCE &&
            abs($targetY->toFloat() - $y) <= self::COORDINATE_TOLERANCE;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    private function findEnemyByNpcId(int $npcId): ?array
    {
        $enemies = $this->assignments['dungeonEnemies'] ?? null;
        if (!is_array($enemies)) {
            return null;
        }

        foreach ($enemies as $enemy) {
            if (!is_array($enemy)) {
                continue;
            }

            $id = $enemy['id'] ?? null;
            if ($id instanceof LuaLiteral && $id->toInt() === $npcId) {
                return $enemy;
            }
        }

        return null;
    }
}
