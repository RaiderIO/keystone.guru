<?php

namespace App\Console\Commands\Spike;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\PathFinding\PathFindingServiceInterface;
use Illuminate\Console\Command;
use SplPriorityQueue;

/**
 * Throwaway spike: build a walkability graph from raw combat log player positions and
 * compare A* walking distance between pack centroids with straight-line distance.
 */
class WalkabilityGraphSpikeCommand extends Command
{
    protected $signature = 'spike:walkability
        {--dir=combatlogs/bv : storage/app sub dir holding run_<id>_segment_<n>.txt}
        {--dungeon=the_blinding_vale}
        {--cell=3 : grid cell size in yards}
        {--min-runs=2 : minimum distinct runs that traversed an edge}
        {--holdout=3 : number of runs held out for evaluation}
        {--max-pair=100 : max straight-line distance of evaluated pack pairs}
        {--sweep : measure coverage vs number of runs}
        {--render : draw PNGs}
        {--zoom=3 : tile zoom used as render background}
        {--max-step=15 : max yards between consecutive samples to count as walking}
        {--pair=* : explicit pack id pair a-b to report/render}';

    private const OUT = 'spike5658';

    private float $cell;

    private float $minX;

    private float $minY;

    private int $gw;

    private int $gh;

    private Floor $floor;

    private bool $labelPacks = true;

    private array $explicit = [];

    public function handle(CoordinatesServiceInterface $coordinatesService, PathFindingServiceInterface $pathFindingService): int
    {
        ini_set('memory_limit', '6G');
        $this->cell = (float)$this->option('cell');
        $dungeon    = Dungeon::where('key', $this->option('dungeon'))->firstOrFail();
        /** @var Floor $floor */
        $floor       = $dungeon->floors()->where('facade', 0)->where('default', 1)->firstOrFail();
        $this->floor = $floor;
        $this->minX  = min($floor->ingame_min_x, $floor->ingame_max_x) - 50;
        $this->minY  = min($floor->ingame_min_y, $floor->ingame_max_y) - 50;
        $this->gw    = (int)ceil((abs($floor->ingame_max_x - $floor->ingame_min_x) + 100) / $this->cell);
        $this->gh    = (int)ceil((abs($floor->ingame_max_y - $floor->ingame_min_y) + 100) / $this->cell);
        $this->info(sprintf('floor %d ui_map_id %d grid %dx%d cell %.1f', $floor->id, $floor->ui_map_id, $this->gw, $this->gh, $this->cell));

        // ---- Pass 1: positions per run (cached) ----
        $runs = $this->loadRuns((int)$floor->ui_map_id);
        $runs   = array_filter($runs, fn(array $players) => count($players) > 0);
        $runIds = array_keys($runs);
        sort($runIds);
        $this->info(sprintf('runs loaded: %d', count($runIds)));

        $holdoutCount = (int)$this->option('holdout');
        $holdout      = array_slice($runIds, 0, $holdoutCount);
        $train        = array_slice($runIds, $holdoutCount);

        // ---- Per run edge sets ----
        $t0       = microtime(true);
        $runEdges = [];
        $stats    = ['steps' => 0, 'gap' => 0, 'fast' => 0, 'still' => 0, 'used' => 0];
        foreach ($runIds as $runId) {
            $runEdges[$runId] = $this->edgesForRun($runs[$runId], $stats);
        }
        $this->info(sprintf('edge extraction %.1fs %s', microtime(true) - $t0, json_encode($stats)));

        $minRuns = (int)$this->option('min-runs');

        // ---- Packs ----
        $packs = $this->loadPacks($dungeon, $coordinatesService);
        $this->info(sprintf('packs: %d', count($packs)));

        if ($this->option('sweep')) {
            $this->sweep($train, $holdout, $runs, $runEdges, $packs, $minRuns);
        }

        // ---- Full graph from train runs ----
        [$adj, $edgeRuns] = $this->buildGraph($train, $runEdges, $minRuns);
        $this->info(sprintf('graph (train=%d, min-runs=%d): nodes=%d edges=%d', count($train), $minRuns, count($adj), count($edgeRuns)));

        $this->evaluateHoldout($holdout, $runs, $adj);
        $pairs = $this->evaluatePairs($packs, $adj);

        // ---- Bridge / same x,y merge heuristic ----
        $flagged = $this->bridgeHeuristic($train, $runs, $adj, $pairs);

        // ---- Cross check with PathFindingService ----
        $this->crossCheck($pathFindingService, $coordinatesService, $adj, $pairs);

        if ($this->option('render')) {
            $this->render($coordinatesService, $adj, $edgeRuns, $packs, $pairs, $flagged, count($train));
        }

        // Graph built from all runs for the final numbers.
        [$adjAll, $edgeRunsAll] = $this->buildGraph($runIds, $runEdges, $minRuns);
        $this->info(sprintf('graph (all=%d, min-runs=%d): nodes=%d edges=%d', count($runIds), $minRuns, count($adjAll), count($edgeRunsAll)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, array<int, array{float, float, float}>>> runId => player => [t, x, y]
     */
    private function loadRuns(int $uiMapId): array
    {
        $dir    = storage_path('app/' . $this->option('dir'));
        $outDir = storage_path('app/' . self::OUT . '/pos');
        @mkdir($outDir, 0777, true);
        $files = glob($dir . '/run_*_segment_*.txt');
        $byRun = [];
        foreach ($files as $file) {
            if (preg_match('/run_(\d+)_segment_(\d+)\.txt$/', $file, $m)) {
                $byRun[(int)$m[1]][(int)$m[2]] = $file;
            }
        }

        $regex = '/^(\d+)\/(\d+)\/\d+ (\d+):(\d+):([\d.]+)(?:[-+]\d+)?\s+[A-Z_]+,.*?,(Player-\d+-[0-9A-F]+),0000000000000000,-?\d+,-?\d+,-?\d+,-?\d+,-?\d+,-?\d+,-?\d+,-?\d+,[-\d:]+,[-\d:]+,[-\d:]+,[-\d:]+,(-?[\d.]+),(-?[\d.]+),(\d+),/';
        $runs  = [];
        foreach ($byRun as $runId => $segments) {
            $cache = sprintf('%s/%d.json', $outDir, $runId);
            if (file_exists($cache)) {
                $runs[$runId] = json_decode(file_get_contents($cache), true);
                continue;
            }
            ksort($segments);
            $players = [];
            $lines   = 0;
            $hits    = 0;
            foreach ($segments as $file) {
                $fh = fopen($file, 'r');
                while (($line = fgets($fh)) !== false) {
                    $lines++;
                    if (!preg_match($regex, $line, $m)) {
                        continue;
                    }
                    if ((int)$m[9] !== $uiMapId) {
                        continue;
                    }
                    $hits++;
                    $t = ((int)$m[1] * 31 + (int)$m[2]) * 86400 + (int)$m[3] * 3600 + (int)$m[4] * 60 + (float)$m[5];
                    // Same convention as AdvancedDataV22: ingame x = -pos2, y = pos1
                    $x    = -(float)$m[8];
                    $y    = (float)$m[7];
                    $p    = &$players[$m[6]];
                    $last = $p === null ? null : $p[count($p) - 1];
                    if ($last === null || $last[0] !== $t || $last[1] !== $x || $last[2] !== $y) {
                        $p[] = [$t, $x, $y];
                    }
                    unset($p);
                }
                fclose($fh);
            }
            foreach ($players as &$p) {
                usort($p, fn($a, $b) => $a[0] <=> $b[0]);
            }
            unset($p);
            $total = array_sum(array_map('count', $players));
            $span  = 0;
            foreach ($players as $p) {
                $span = max($span, end($p)[0] - $p[0][0]);
            }
            $this->line(sprintf('run %d: lines=%d positioned=%d players=%d samples=%d span=%.0fs (%.2f samples/player/s)',
                $runId, $lines, $hits, count($players), $total, $span, $span > 0 ? $total / max(1, count($players)) / $span : 0));
            file_put_contents($cache, json_encode($players));
            $runs[$runId] = $players;
        }

        return $runs;
    }

    private function cellOf(float $x, float $y): int
    {
        $cx = (int)floor(($x - $this->minX) / $this->cell);
        $cy = (int)floor(($y - $this->minY) / $this->cell);
        if ($cx < 0 || $cy < 0 || $cx >= $this->gw || $cy >= $this->gh) {
            return -1;
        }

        return $cy * $this->gw + $cx;
    }

    /**
     * @return array{float, float}
     */
    private function cellCenter(int $c): array
    {
        return [$this->minX + (($c % $this->gw) + 0.5) * $this->cell, $this->minY + (intdiv($c, $this->gw) + 0.5) * $this->cell];
    }

    /**
     * Whether a consecutive sample pair counts as continuous walking.
     */
    private function isWalkStep(array $a, array $b, array &$stats): bool
    {
        $stats['steps']++;
        $dt = $b[0] - $a[0];
        $d  = hypot($b[1] - $a[1], $b[2] - $a[2]);
        if ($dt > 2.0 || $dt <= 0) {
            $stats['gap']++;

            return false;
        }
        if ($d > (float)$this->option('max-step') || ($d > 3 && $d / $dt > 14)) {
            $stats['fast']++;

            return false;
        }
        if ($d < 0.01) {
            $stats['still']++;

            return false;
        }
        $stats['used']++;

        return true;
    }

    /**
     * 8-connected Bresenham between two cells.
     *
     * @return int[]
     */
    private function rasterize(int $c0, int $c1): array
    {
        $x0    = $c0 % $this->gw;
        $y0    = intdiv($c0, $this->gw);
        $x1    = $c1 % $this->gw;
        $y1    = intdiv($c1, $this->gw);
        $dx    = abs($x1 - $x0);
        $dy    = -abs($y1 - $y0);
        $sx    = $x0 < $x1 ? 1 : -1;
        $sy    = $y0 < $y1 ? 1 : -1;
        $err   = $dx + $dy;
        $cells = [$c0];
        while ($x0 !== $x1 || $y0 !== $y1) {
            $e2 = 2 * $err;
            if ($e2 >= $dy) {
                $err += $dy;
                $x0  += $sx;
            }
            if ($e2 <= $dx) {
                $err += $dx;
                $y0  += $sy;
            }
            $cells[] = $y0 * $this->gw + $x0;
        }

        return $cells;
    }

    /**
     * @return array<int, int> edgeKey => traversal count within this run
     */
    private function edgesForRun(array $players, array &$stats): array
    {
        $n     = $this->gw * $this->gh;
        $edges = [];
        foreach ($players as $samples) {
            for ($i = 1, $c = count($samples); $i < $c; $i++) {
                if (!$this->isWalkStep($samples[$i - 1], $samples[$i], $stats)) {
                    continue;
                }
                $c0 = $this->cellOf($samples[$i - 1][1], $samples[$i - 1][2]);
                $c1 = $this->cellOf($samples[$i][1], $samples[$i][2]);
                if ($c0 < 0 || $c1 < 0 || $c0 === $c1) {
                    continue;
                }
                $cells = $this->rasterize($c0, $c1);
                for ($k = 1, $kc = count($cells); $k < $kc; $k++) {
                    $a   = min($cells[$k - 1], $cells[$k]);
                    $b   = max($cells[$k - 1], $cells[$k]);
                    $key = $a * $n + $b;
                    $edges[$key] = ($edges[$key] ?? 0) + 1;
                }
            }
        }

        return $edges;
    }

    /**
     * @return array{array<int, int[]>, array<int, int>} adjacency, edgeKey => distinct run count
     */
    private function buildGraph(array $runIds, array $runEdges, int $minRuns): array
    {
        $n        = $this->gw * $this->gh;
        $edgeRuns = [];
        foreach ($runIds as $runId) {
            foreach ($runEdges[$runId] as $key => $count) {
                $edgeRuns[$key] = ($edgeRuns[$key] ?? 0) + 1;
            }
        }
        $adj = [];
        foreach ($edgeRuns as $key => $count) {
            if ($count < $minRuns) {
                unset($edgeRuns[$key]);
                continue;
            }
            $a       = intdiv($key, $n);
            $b       = $key % $n;
            $adj[$a][] = $b;
            $adj[$b][] = $a;
        }

        return [$adj, $edgeRuns];
    }

    /**
     * @return array<int, array{id: int, x: float, y: float, lat: float, lng: float, n: int}>
     */
    private function loadPacks(Dungeon $dungeon, CoordinatesServiceInterface $coordinatesService): array
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $enemies        = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('floor_id', $this->floor->id)
            ->whereNotNull('enemy_pack_id')
            ->get();
        $packs = [];
        foreach ($enemies->groupBy('enemy_pack_id') as $packId => $packEnemies) {
            $sx = $sy = $slat = $slng = 0;
            foreach ($packEnemies as $enemy) {
                $xy   = $coordinatesService->calculateIngameLocationForMapLocation(new LatLng($enemy->lat, $enemy->lng, $this->floor));
                $sx   += $xy->getX();
                $sy   += $xy->getY();
                $slat += $enemy->lat;
                $slng += $enemy->lng;
            }
            $c       = $packEnemies->count();
            $packs[] = ['id' => (int)$packId, 'x' => $sx / $c, 'y' => $sy / $c, 'lat' => $slat / $c, 'lng' => $slng / $c, 'n' => $c];
        }

        return $packs;
    }

    /**
     * Nearest graph node within $radius yards.
     */
    private function snap(float $x, float $y, array $adj, float $radius = 15): ?int
    {
        $c = $this->cellOf($x, $y);
        if ($c >= 0 && isset($adj[$c])) {
            return $c;
        }
        $r    = (int)ceil($radius / $this->cell);
        $cx   = (int)floor(($x - $this->minX) / $this->cell);
        $cy   = (int)floor(($y - $this->minY) / $this->cell);
        $best = null;
        $bd   = INF;
        for ($dy = -$r; $dy <= $r; $dy++) {
            for ($dx = -$r; $dx <= $r; $dx++) {
                $nx = $cx + $dx;
                $ny = $cy + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $this->gw || $ny >= $this->gh) {
                    continue;
                }
                $nc = $ny * $this->gw + $nx;
                if (!isset($adj[$nc])) {
                    continue;
                }
                [$px, $py] = $this->cellCenter($nc);
                $d         = hypot($px - $x, $py - $y);
                if ($d < $bd && $d <= $radius) {
                    $bd   = $d;
                    $best = $nc;
                }
            }
        }

        return $best;
    }

    /**
     * @return array{float, int[]}|null distance, path cells
     */
    private function astar(array $adj, int $from, int $to): ?array
    {
        if ($from === $to) {
            return [0.0, [$from]];
        }
        [$tx, $ty] = $this->cellCenter($to);
        $g         = [$from => 0.0];
        $prev      = [];
        $closed    = [];
        $q         = new SplPriorityQueue();
        $q->insert($from, 0);
        while (!$q->isEmpty()) {
            $cur = $q->extract();
            if (isset($closed[$cur])) {
                continue;
            }
            $closed[$cur] = true;
            if ($cur === $to) {
                $path = [$to];
                while (isset($prev[$cur])) {
                    $cur    = $prev[$cur];
                    $path[] = $cur;
                }

                return [$g[$to], array_reverse($path)];
            }
            [$cx, $cy] = $this->cellCenter($cur);
            foreach ($adj[$cur] ?? [] as $nb) {
                if (isset($closed[$nb])) {
                    continue;
                }
                [$nx, $ny] = $this->cellCenter($nb);
                $ng        = $g[$cur] + hypot($nx - $cx, $ny - $cy);
                if (!isset($g[$nb]) || $ng < $g[$nb]) {
                    $g[$nb]    = $ng;
                    $prev[$nb] = $cur;
                    $q->insert($nb, -($ng + hypot($tx - $nx, $ty - $ny)));
                }
            }
        }

        return null;
    }

    /**
     * Largest connected component share of the node set.
     */
    private function largestComponentShare(array $adj): float
    {
        $seen = [];
        $best = 0;
        foreach ($adj as $start => $_) {
            if (isset($seen[$start])) {
                continue;
            }
            $stack        = [$start];
            $seen[$start] = true;
            $size         = 0;
            while ($stack) {
                $cur = array_pop($stack);
                $size++;
                foreach ($adj[$cur] as $nb) {
                    if (!isset($seen[$nb])) {
                        $seen[$nb] = true;
                        $stack[]   = $nb;
                    }
                }
            }
            $best = max($best, $size);
        }

        return count($adj) ? $best / count($adj) : 0;
    }

    private function sweep(array $train, array $holdout, array $runs, array $runEdges, array $packs, int $minRuns): void
    {
        [$adjRef] = $this->buildGraph($train, $runEdges, $minRuns);
        $refPairs = $this->pairDistances($packs, $adjRef);
        $rows     = [];
        foreach ([1, 2, 3, 5, 8, 12, 17, 25, 35] as $n) {
            if ($n > count($train)) {
                continue;
            }
            foreach ([1, 2, 3] as $mr) {
                if ($mr > $n) {
                    continue;
                }
                [$adj, $edgeRuns] = $this->buildGraph(array_slice($train, 0, $n), $runEdges, $mr);
                // coverage of held-out positions
                $tot = $hit = 0;
                foreach ($holdout as $runId) {
                    foreach ($runs[$runId] as $samples) {
                        foreach ($samples as $s) {
                            $c = $this->cellOf($s[1], $s[2]);
                            if ($c < 0) {
                                continue;
                            }
                            $tot++;
                            if ($this->snap($s[1], $s[2], $adj, $this->cell * 1.5) !== null) {
                                $hit++;
                            }
                        }
                    }
                }
                $snapped = 0;
                foreach ($packs as $p) {
                    if ($this->snap($p['x'], $p['y'], $adj) !== null) {
                        $snapped++;
                    }
                }
                $pd         = $this->pairDistances($packs, $adj);
                $reach      = 0;
                $errs       = [];
                foreach ($refPairs as $k => $ref) {
                    if ($ref === null) {
                        continue;
                    }
                    if (($pd[$k] ?? null) !== null) {
                        $reach++;
                        $errs[] = abs($pd[$k] - $ref) / max(1, $ref);
                    }
                }
                sort($errs);
                $refCount = count(array_filter($refPairs, fn($v) => $v !== null));
                $rows[]   = [
                    $n, $mr, count($adj), count($edgeRuns),
                    sprintf('%.1f%%', 100 * $hit / max(1, $tot)),
                    sprintf('%d/%d', $snapped, count($packs)),
                    sprintf('%.1f%%', 100 * $this->largestComponentShare($adj)),
                    sprintf('%d/%d', $reach, $refCount),
                    $errs ? sprintf('%.1f%%', 100 * $errs[intdiv(count($errs), 2)]) : '-',
                    $errs ? sprintf('%.1f%%', 100 * $errs[(int)floor(count($errs) * 0.9)]) : '-',
                ];
            }
        }
        $this->table(['runs', 'min-runs', 'nodes', 'edges', 'holdout pos covered', 'packs snapped', 'largest comp', 'pairs reachable (vs ref)', 'median |Δ| vs ref', 'p90 |Δ| vs ref'], $rows);
    }

    /**
     * @return array<string, float|null> "i-j" => walking distance
     */
    private function pairDistances(array $packs, array $adj): array
    {
        $max = (float)$this->option('max-pair');
        $out = [];
        $nodes = [];
        foreach ($packs as $i => $p) {
            $nodes[$i] = $this->snap($p['x'], $p['y'], $adj);
        }
        foreach ($packs as $i => $a) {
            foreach ($packs as $j => $b) {
                if ($j <= $i) {
                    continue;
                }
                $s = hypot($a['x'] - $b['x'], $a['y'] - $b['y']);
                if ($s > $max) {
                    continue;
                }
                $r           = ($nodes[$i] !== null && $nodes[$j] !== null) ? $this->astar($adj, $nodes[$i], $nodes[$j]) : null;
                $out["$i-$j"] = $r === null ? null : $r[0];
            }
        }

        return $out;
    }

    private function evaluateHoldout(array $holdout, array $runs, array $adj): void
    {
        $stats  = ['steps' => 0, 'gap' => 0, 'fast' => 0, 'still' => 0, 'used' => 0];
        $rows   = [];
        $nosnap = $noreach = 0;
        foreach ($holdout as $runId) {
            foreach ($runs[$runId] as $samples) {
                // split into continuous chains
                $chains = [];
                $chain  = [$samples[0]];
                for ($i = 1, $c = count($samples); $i < $c; $i++) {
                    if ($this->isWalkStep($samples[$i - 1], $samples[$i], $stats)) {
                        $chain[] = $samples[$i];
                    } else {
                        if (count($chain) > 1) {
                            $chains[] = $chain;
                        }
                        $chain = [$samples[$i]];
                    }
                }
                foreach ($chains as $chain) {
                    $cum = [0.0];
                    for ($i = 1, $c = count($chain); $i < $c; $i++) {
                        $cum[$i] = $cum[$i - 1] + hypot($chain[$i][1] - $chain[$i - 1][1], $chain[$i][2] - $chain[$i - 1][2]);
                    }
                    $lastStart = -INF;
                    for ($i = 0; $i < $c; $i++) {
                        if ($chain[$i][0] - $lastStart < 5) {
                            continue;
                        }
                        // find j with path length >= 40 yd
                        for ($j = $i + 1; $j < $c && $cum[$j] - $cum[$i] < 40; $j++) {
                        }
                        if ($j >= $c) {
                            break;
                        }
                        $lastStart = $chain[$i][0];
                        $p         = $cum[$j] - $cum[$i];
                        $s         = hypot($chain[$j][1] - $chain[$i][1], $chain[$j][2] - $chain[$i][2]);
                        $a         = $this->snap($chain[$i][1], $chain[$i][2], $adj, 6);
                        $b         = $this->snap($chain[$j][1], $chain[$j][2], $adj, 6);
                        if ($a === null || $b === null) {
                            $nosnap++;
                            continue;
                        }
                        $r = $this->astar($adj, $a, $b);
                        if ($r === null) {
                            $noreach++;
                            continue;
                        }
                        $rows[] = [$p, $s, $r[0]];
                    }
                }
            }
        }
        $gp     = array_map(fn($r) => $r[2] / $r[0], $rows);
        $bendy  = array_values(array_filter($rows, fn($r) => $r[1] / $r[0] < 0.75));
        $sorted = fn(array $a) => (function () use ($a) {
            sort($a);

            return $a;
        })();
        $med = fn(array $a) => $a ? $sorted($a)[intdiv(count($a), 2)] : NAN;
        $this->info(sprintf(
            'holdout 40yd walk windows: n=%d nosnap=%d unreachable=%d | median graph/actual=%.2f, graph>1.15*actual=%.1f%%, graph<0.95*straight=%.1f%%',
            count($rows), $nosnap, $noreach, $med($gp),
            100 * count(array_filter($gp, fn($v) => $v > 1.15)) / max(1, count($gp)),
            100 * count(array_filter($rows, fn($r) => $r[2] < 0.95 * $r[1])) / max(1, count($rows)),
        ));
        $this->info(sprintf(
            'holdout bendy windows (straight/actual<0.75): n=%d median straight/actual=%.2f median graph/actual=%.2f',
            count($bendy), $med(array_map(fn($r) => $r[1] / $r[0], $bendy)), $med(array_map(fn($r) => $r[2] / $r[0], $bendy)),
        ));
    }

    /**
     * @return array<int, array{i: int, j: int, s: float, g: float|null, path: int[]}>
     */
    private function evaluatePairs(array $packs, array $adj): array
    {
        $max   = (float)$this->option('max-pair');
        $pairs = [];
        $nodes = [];
        $snapD = [];
        foreach ($packs as $i => $p) {
            $nodes[$i] = $this->snap($p['x'], $p['y'], $adj);
            if ($nodes[$i] !== null) {
                [$cx, $cy] = $this->cellCenter($nodes[$i]);
                $snapD[]   = hypot($cx - $p['x'], $cy - $p['y']);
            }
        }
        sort($snapD);
        $this->info(sprintf('packs snapped %d/%d, median snap %.1fyd, max %.1fyd',
            count($snapD), count($packs), $snapD[intdiv(count($snapD), 2)] ?? 0, end($snapD) ?: 0));

        $t0 = microtime(true);
        foreach ($packs as $i => $a) {
            foreach ($packs as $j => $b) {
                if ($j <= $i) {
                    continue;
                }
                $s = hypot($a['x'] - $b['x'], $a['y'] - $b['y']);
                if ($s > $max) {
                    continue;
                }
                $r       = ($nodes[$i] !== null && $nodes[$j] !== null) ? $this->astar($adj, $nodes[$i], $nodes[$j]) : null;
                $pairs[] = ['i' => $i, 'j' => $j, 's' => $s, 'g' => $r[0] ?? null, 'path' => $r[1] ?? []];
            }
        }
        $dt = microtime(true) - $t0;

        $ok     = array_values(array_filter($pairs, fn($p) => $p['g'] !== null));
        $bothSnapped = count(array_filter($pairs, fn($p) => $nodes[$p['i']] !== null && $nodes[$p['j']] !== null));
        $this->info(sprintf('pairs with both packs snapped: %d, of which unreachable: %d', $bothSnapped, $bothSnapped - count($ok)));
        $ratios = array_map(fn($p) => $p['g'] / max(1, $p['s']), $ok);
        sort($ratios);
        $q = fn($f) => $ratios ? $ratios[min(count($ratios) - 1, (int)floor(count($ratios) * $f))] : NAN;
        $this->info(sprintf('pack pairs <= %dyd: %d, reachable %d, A* %.1fms/pair | walk/straight p10=%.2f p50=%.2f p75=%.2f p90=%.2f max=%.2f | >1.5x: %d, >2x: %d',
            $max, count($pairs), count($ok), 1000 * $dt / max(1, count($pairs)), $q(0.1), $q(0.5), $q(0.75), $q(0.9), end($ratios) ?: NAN,
            count(array_filter($ratios, fn($r) => $r > 1.5)), count(array_filter($ratios, fn($r) => $r > 2))));

        usort($ok, fn($a, $b) => ($b['g'] - $b['s']) <=> ($a['g'] - $a['s']));
        $rows = [];
        foreach (array_slice($ok, 0, 15) as $p) {
            $rows[] = [$packs[$p['i']]['id'], $packs[$p['j']]['id'], sprintf('%.0f', $p['s']), sprintf('%.0f', $p['g']), sprintf('%.2f', $p['g'] / $p['s'])];
        }
        $this->table(['pack A', 'pack B', 'straight yd', 'walk yd', 'ratio'], $rows);

        $byId = [];
        foreach ($packs as $i => $p) {
            $byId[$p['id']] = $i;
        }
        $rows = [];
        foreach ($this->option('pair') as $pairStr) {
            [$a, $b] = array_map('intval', explode('-', $pairStr));
            $i       = $byId[$a];
            $j       = $byId[$b];
            $s       = hypot($packs[$i]['x'] - $packs[$j]['x'], $packs[$i]['y'] - $packs[$j]['y']);
            $r       = ($nodes[$i] !== null && $nodes[$j] !== null) ? $this->astar($adj, $nodes[$i], $nodes[$j]) : null;
            $this->explicit[] = ['i' => $i, 'j' => $j, 's' => $s, 'g' => $r[0] ?? null, 'path' => $r[1] ?? []];
            $rows[]  = [$a, $b, sprintf('%.0f', $s), $r ? sprintf('%.0f', $r[0]) : 'unreachable', $r ? sprintf('%.2f', $r[0] / $s) : '-'];
        }
        if ($rows) {
            $this->info('explicit pairs:');
            $this->table(['pack A', 'pack B', 'straight yd', 'walk yd', 'ratio'], $rows);
        }

        return $ok;
    }

    /**
     * Same x/y, different height heuristic: a cell crossed by two roughly perpendicular flows with
     * (almost) no observed turns between them looks like a bridge over a path.
     *
     * @return int[] flagged coarse cell centers as fine cell ids
     */
    private function bridgeHeuristic(array $train, array $runs, array $adj, array $pairs): array
    {
        $coarse = 9.0;
        $gw     = (int)ceil($this->gw * $this->cell / $coarse);
        $stats  = ['steps' => 0, 'gap' => 0, 'fast' => 0, 'still' => 0, 'used' => 0];
        /** @var array<int, array{straight: array<int, array<int, true>>, turns: int, passes: int}> $cells */
        $cells = [];
        foreach ($train as $runId) {
            foreach ($runs[$runId] as $samples) {
                $chain = [];
                $flush = function () use (&$chain, &$cells, $coarse, $gw, $runId) {
                    $c = count($chain);
                    if ($c < 3) {
                        $chain = [];

                        return;
                    }
                    $cum = [0.0];
                    for ($i = 1; $i < $c; $i++) {
                        $cum[$i] = $cum[$i - 1] + hypot($chain[$i][1] - $chain[$i - 1][1], $chain[$i][2] - $chain[$i - 1][2]);
                    }
                    $prevCell = null;
                    $lo       = 0;
                    $hi       = 0;
                    for ($i = 0; $i < $c; $i++) {
                        $cell = (int)floor(($chain[$i][2] - $this->minY) / $coarse) * $gw + (int)floor(($chain[$i][1] - $this->minX) / $coarse);
                        if ($cell === $prevCell) {
                            continue;
                        }
                        $prevCell = $cell;
                        while ($lo < $i && $cum[$i] - $cum[$lo + 1] >= 8) {
                            $lo++;
                        }
                        $hi = max($hi, $i);
                        while ($hi < $c - 1 && $cum[$hi] - $cum[$i] < 8) {
                            $hi++;
                        }
                        if ($cum[$i] - $cum[$lo] < 6 || $cum[$hi] - $cum[$i] < 6) {
                            continue;
                        }
                        $hin  = atan2($chain[$i][2] - $chain[$lo][2], $chain[$i][1] - $chain[$lo][1]);
                        $hout = atan2($chain[$hi][2] - $chain[$i][2], $chain[$hi][1] - $chain[$i][1]);
                        $turn = abs(atan2(sin($hout - $hin), cos($hout - $hin)));
                        $cells[$cell]['passes'] = ($cells[$cell]['passes'] ?? 0) + 1;
                        if ($turn > M_PI / 3) {
                            $cells[$cell]['turns'] = ($cells[$cell]['turns'] ?? 0) + 1;
                        } elseif ($turn < M_PI / 8) {
                            $axis = ((int)round(fmod($hin + 2 * M_PI, M_PI) / (M_PI / 4))) % 4;
                            $cells[$cell]['straight'][$axis][$runId] = true;
                        }
                    }
                    $chain = [];
                };
                $chain = [$samples[0]];
                for ($i = 1, $c = count($samples); $i < $c; $i++) {
                    if ($this->isWalkStep($samples[$i - 1], $samples[$i], $stats)) {
                        $chain[] = $samples[$i];
                    } else {
                        $flush();
                        $chain = [$samples[$i]];
                    }
                }
                $flush();
            }
        }
        $candidates = 0;
        $flagged    = [];
        foreach ($cells as $cell => $info) {
            $axes = array_map('count', $info['straight'] ?? []);
            // perpendicular pairs: 0/2 and 1/3
            foreach ([[0, 2], [1, 3]] as [$a, $b]) {
                if (($axes[$a] ?? 0) >= 3 && ($axes[$b] ?? 0) >= 3) {
                    $candidates++;
                    if (($info['turns'] ?? 0) / max(1, $info['passes']) < 0.03) {
                        $flagged[] = $cell;
                    }
                    break;
                }
            }
        }
        // Convert flagged coarse cells to fine cell ids and count pack pairs whose path crosses one.
        $fineFlags = [];
        $flagSet   = [];
        foreach ($flagged as $cell) {
            $x                  = $this->minX + (($cell % $gw) + 0.5) * $coarse;
            $y                  = $this->minY + (intdiv($cell, $gw) + 0.5) * $coarse;
            $fineFlags[]        = $this->cellOf($x, $y);
            $flagSet[$cell]     = true;
        }
        $pairsThrough = 0;
        foreach ($pairs as $p) {
            foreach ($p['path'] as $fc) {
                [$x, $y] = $this->cellCenter($fc);
                $cc      = (int)floor(($y - $this->minY) / $coarse) * $gw + (int)floor(($x - $this->minX) / $coarse);
                if (isset($flagSet[$cc])) {
                    $pairsThrough++;
                    break;
                }
            }
        }
        $this->info(sprintf('crossing heuristic (%.0fyd cells): cells with passes=%d, perpendicular-flow cells=%d, of which no-turn (bridge-like)=%d; pack-pair paths through a bridge-like cell: %d/%d',
            $coarse, count($cells), $candidates, count($flagged), $pairsThrough, count($pairs)));

        return $fineFlags;
    }

    private function crossCheck(PathFindingServiceInterface $pathFindingService, CoordinatesServiceInterface $coordinatesService, array $adj, array $pairs): void
    {
        $t0    = microtime(true);
        $nodes = [];
        $edges = [];
        foreach ($adj as $c => $nbs) {
            [$x, $y]       = $this->cellCenter($c);
            $xy            = new IngameXY($x, $y, $this->floor);
            $nodes[(string)$c] = new PathNode((string)$c, $coordinatesService->calculateMapLocationForIngameLocation($xy), $xy);
            foreach ($nbs as $nb) {
                [$nx, $ny] = $this->cellCenter($nb);
                $edges[]   = new PathEdge((string)$c, (string)$nb, hypot($nx - $x, $ny - $y));
            }
        }
        $build = microtime(true) - $t0;
        $t1    = microtime(true);
        $n     = 0;
        $maxDiff = 0;
        foreach (array_slice($pairs, 0, 10) as $p) {
            $path = $pathFindingService->findShortestPath($nodes, $edges, (string)$p['path'][0], (string)end($p['path']));
            $len  = 0;
            for ($i = 1; $i < count($path); $i++) {
                $len += $coordinatesService->distanceIngameXY(
                    $coordinatesService->calculateIngameLocationForMapLocation($path[$i - 1]),
                    $coordinatesService->calculateIngameLocationForMapLocation($path[$i]),
                );
            }
            $maxDiff = max($maxDiff, abs($len - $p['g']));
            $n++;
        }
        $this->info(sprintf('PathFindingService cross-check: %d nodes/%d PathEdges built in %.2fs, %.0fms per findShortestPath, max |Dijkstra-A*| = %.2fyd over %d pairs',
            count($nodes), count($edges), $build, 1000 * (microtime(true) - $t1) / max(1, $n), $maxDiff, $n));
    }

    private function render(CoordinatesServiceInterface $coordinatesService, array $adj, array $edgeRuns, array $packs, array $pairs, array $flagged, int $runCount): void
    {
        $zoom  = (int)$this->option('zoom');
        $tiles = 2 ** $zoom;
        $scale = $tiles;
        $img   = imagecreatetruecolor(384 * $tiles, 256 * $tiles);
        $tdir  = storage_path(sprintf('app/%s/tiles/%d', self::OUT, $zoom));
        for ($x = 0; $x < $tiles; $x++) {
            for ($y = 0; $y < $tiles; $y++) {
                $f = sprintf('%s/%d_%d.png', $tdir, $x, $y);
                if (file_exists($f)) {
                    $t = imagecreatefrompng($f);
                    imagecopy($img, $t, $x * 384, $y * 256, 0, 0, 384, 256);
                }
            }
        }
        // dim background
        imagefilledrectangle($img, 0, 0, 384 * $tiles, 256 * $tiles, imagecolorallocatealpha($img, 0, 0, 0, 60));
        $toPx = function (float $x, float $y) use ($coordinatesService, $scale) {
            $ll = $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $this->floor));

            return [(int)round($ll->getLng() * $scale), (int)round(-$ll->getLat() * $scale)];
        };
        $cellPx = [];
        foreach ($adj as $c => $_) {
            $cellPx[$c] = $toPx(...$this->cellCenter($c));
        }
        $base = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagecopy($base, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        $n    = $this->gw * $this->gh;
        imagesetthickness($img, 2);
        foreach ($edgeRuns as $key => $count) {
            $a    = intdiv($key, $n);
            $b    = $key % $n;
            $f    = min(1, $count / max(1, $runCount));
            $col  = imagecolorallocate($img, (int)(255 * (1 - $f)), (int)(120 + 135 * $f), (int)(255 * $f));
            imageline($img, $cellPx[$a][0], $cellPx[$a][1], $cellPx[$b][0], $cellPx[$b][1], $col);
        }
        $this->drawPacks($img, $packs, $scale);
        $red = imagecolorallocate($img, 255, 0, 0);
        foreach ($flagged as $fc) {
            [$px, $py] = $toPx(...$this->cellCenter($fc));
            imagesetthickness($img, 2);
            imageellipse($img, $px, $py, 22, 22, $red);
        }
        $out = storage_path(sprintf('app/%s/graph.png', self::OUT));
        imagepng($img, $out);
        $this->info('wrote ' . $out);

        // Paths image
        $this->labelPacks = false;
        $img2   = $base;
        $colors = [[255, 80, 80], [80, 200, 255], [255, 220, 0], [120, 255, 120], [255, 120, 255], [255, 160, 40]];
        $gray   = imagecolorallocatealpha($img2, 200, 200, 200, 80);
        imagesetthickness($img2, 1);
        foreach ($edgeRuns as $key => $count) {
            $a = intdiv($key, $n);
            $b = $key % $n;
            imageline($img2, $cellPx[$a][0], $cellPx[$a][1], $cellPx[$b][0], $cellPx[$b][1], $gray);
        }
        $this->drawPacks($img2, $packs, $scale);
        $chosen = array_values(array_filter($this->explicit, fn($p) => $p['g'] !== null));
        foreach ($chosen ? [] : $pairs as $p) {
            if (count($chosen) >= count($colors)) {
                break;
            }
            // skip pairs sharing a pack with one already chosen for legibility
            foreach ($chosen as $c) {
                if (in_array($p['i'], [$c['i'], $c['j']]) || in_array($p['j'], [$c['i'], $c['j']])) {
                    continue 2;
                }
            }
            $chosen[] = $p;
        }
        $font = 5;
        foreach ($chosen as $k => $p) {
            $col = imagecolorallocate($img2, ...$colors[$k % count($colors)]);
            imagesetthickness($img2, 4);
            for ($i = 1; $i < count($p['path']); $i++) {
                [$x0, $y0] = $cellPx[$p['path'][$i - 1]];
                [$x1, $y1] = $cellPx[$p['path'][$i]];
                imageline($img2, $x0, $y0, $x1, $y1, $col);
            }
            $a = $packs[$p['i']];
            $b = $packs[$p['j']];
            imagesetthickness($img2, 2);
            imageline($img2, (int)($a['lng'] * $scale), (int)(-$a['lat'] * $scale), (int)($b['lng'] * $scale), (int)(-$b['lat'] * $scale), $col);
            $label = sprintf('%d: %.0f vs %.0f yd', $k + 1, $p['g'], $p['s']);
            imagestring($img2, $font, (int)($a['lng'] * $scale) + 8, (int)(-$a['lat'] * $scale) - 8, $label, $col);
            $this->line(sprintf('render pair %d: packs %d-%d straight %.0f walk %.0f', $k + 1, $a['id'], $b['id'], $p['s'], $p['g']));
        }
        $out2 = storage_path(sprintf('app/%s/paths.png', self::OUT));
        imagepng($img2, $out2);
        $this->info('wrote ' . $out2);
    }

    private function drawPacks($img, array $packs, int $scale): void
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        foreach ($packs as $p) {
            $px = (int)($p['lng'] * $scale);
            $py = (int)(-$p['lat'] * $scale);
            imagefilledellipse($img, $px, $py, 12, 12, $white);
            imageellipse($img, $px, $py, 12, 12, $black);
            if ($this->labelPacks) {
                imagestring($img, 2, $px + 7, $py + 2, (string)$p['id'], $white);
            }
        }
    }
}
