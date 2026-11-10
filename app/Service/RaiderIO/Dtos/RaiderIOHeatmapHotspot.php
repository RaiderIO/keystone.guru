<?php

namespace App\Service\RaiderIO\Dtos;

use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;

/**
 * One hotspot of a Raider.IO heatmap grid response, in the in-game coordinates of its zone floor. Area and centroid
 * are measured here, in-game, never from the lat/lngs the hotspot is displayed at.
 */
final readonly class RaiderIOHeatmapHotspot
{
    /**
     * @param list<IngameXY>                       $vertices
     * @param list<array{npcId: int, deaths: int}> $npcs     Most deadly first
     */
    private function __construct(
        public Floor    $floor,
        public array    $vertices,
        public float    $area,
        public IngameXY $centroid,
        public int      $deaths,
        public array    $npcs,
    ) {
    }

    /**
     * Even-odd ray cast against the polygon.
     */
    public function containsPoint(IngameXY $ingameXY): bool
    {
        $x      = $ingameXY->getX();
        $y      = $ingameXY->getY();
        $result = false;

        $vertexCount = count($this->vertices);
        for ($i = 0, $j = $vertexCount - 1; $i < $vertexCount; $j = $i++) {
            $xi = $this->vertices[$i]->getX();
            $yi = $this->vertices[$i]->getY();
            $xj = $this->vertices[$j]->getX();
            $yj = $this->vertices[$j]->getY();

            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $result = !$result;
            }
        }

        return $result;
    }

    /**
     * @param  array<array-key, mixed> $hotspot One entry of hotspotsByFloor[floorId]
     * @return self|null               Null when the hotspot has no usable polygon or no NPC deaths
     */
    public static function fromArray(Floor $floor, array $hotspot): ?self
    {
        $vertices = [];
        foreach ((array)($hotspot['polygon'] ?? []) as $vertex) {
            if (!is_array($vertex) || !isset($vertex[0], $vertex[1]) || !is_numeric($vertex[0]) || !is_numeric($vertex[1])) {
                return null;
            }

            $vertices[] = new IngameXY((float)$vertex[0], (float)$vertex[1], $floor);
        }

        // A closed ring repeats its first vertex at the end
        $vertexCount = count($vertices);
        if ($vertexCount > 1 &&
            $vertices[0]->getX() === $vertices[$vertexCount - 1]->getX() &&
            $vertices[0]->getY() === $vertices[$vertexCount - 1]->getY()) {
            array_pop($vertices);
        }

        $npcs = [];
        foreach ((array)($hotspot['npcs'] ?? []) as $npc) {
            if (!is_array($npc) || !is_numeric($npc['npcId'] ?? null) || !is_numeric($npc['deaths'] ?? null) || (int)$npc['deaths'] <= 0) {
                continue;
            }

            $npcs[] = [
                'npcId'  => (int)$npc['npcId'],
                'deaths' => (int)$npc['deaths'],
            ];
        }

        if (count($vertices) < 3 || $npcs === []) {
            return null;
        }

        // Shoelace formula for the signed area and the area-weighted centroid
        $signedAreaTwice = 0.0;
        $centroidX       = 0.0;
        $centroidY       = 0.0;
        $vertexCount     = count($vertices);
        for ($i = 0; $i < $vertexCount; $i++) {
            $current = $vertices[$i];
            $next    = $vertices[($i + 1) % $vertexCount];
            $cross   = $current->getX() * $next->getY() - $next->getX() * $current->getY();

            $signedAreaTwice += $cross;
            $centroidX += ($current->getX() + $next->getX()) * $cross;
            $centroidY += ($current->getY() + $next->getY()) * $cross;
        }

        if ($signedAreaTwice == 0.0) {
            return null;
        }

        usort($npcs, static fn(array $a, array $b): int => $b['deaths'] <=> $a['deaths']);

        return new self(
            $floor,
            $vertices,
            abs($signedAreaTwice) / 2,
            new IngameXY($centroidX / (3 * $signedAreaTwice), $centroidY / (3 * $signedAreaTwice), $floor),
            array_sum(array_column($npcs, 'deaths')),
            $npcs,
        );
    }
}
