<?php

use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use Illuminate\Support\Collection;

/**
 * A compact "route fingerprint": one bar per pull, bar height proportional to the pull's enemy forces,
 * so a viewer instantly gauges the shape of a route (a few big pulls versus many small ones).
 * Boss pulls render as full-height accent bars, turning the graph into a timeline of trash profile
 * plus boss milestones. Pulls that grant no enemy forces and contain no boss are omitted entirely.
 *
 * @var Collection<int, KillZoneEnemyForces> $pullForces  Enemy forces + boss flag per kill zone, ordered by kill zone index.
 * @var int|null                                                    $chartHeight Height of the chart in pixels. Defaults to 26.
 * @var string|null                                                 $fill        SVG fill color for regular (trash) bars. Defaults to a translucent white.
 * @var string|null                                                 $bossFill    SVG fill color for full-height boss bars. Defaults to a translucent amber.
 * @var string                                                     $graphClass  CSS class applied to the wrapping span.
 * @var string                                                     $tooltipKey  Translation key for the "N pulls" tooltip (trans_choice).
 */

// Sane defaults so most callers only need to pass pullForces/graphClass/tooltipKey; a caller with a
// differently-sized or -colored graph (e.g. the denser leaderboard rows) overrides what it needs.
$chartHeight ??= 26;
$fill ??= 'rgba(255, 255, 255, 0.6)';
$bossFill ??= 'rgba(240, 180, 60, 0.95)';

// Routes rarely exceed this many pulls; cap the bar count to keep the graph tidy while the tooltip keeps the true total
$maxBars = 30;

// The tooltip always reflects the route's real pull count, regardless of how many bars survive filtering
$pullCount = $pullForces->count();
?>
@if( $pullCount > 0 )
    <?php
    $barWidth = 3;
    $barGap   = 1;
    $minBar   = 2;
    // Zero-forces pulls without a boss carry no information - drop them before capping so they
    // neither render as noise nor eat into the bar budget
    $bars = $pullForces
        ->filter(static fn(KillZoneEnemyForces $pull) => $pull->enemyForces > 0 || $pull->hasBoss)
        ->take($maxBars)
        ->values();
    // Trash bars normalize against the largest trash pull; boss bars are always full height
    $maxForces  = (int) $bars->where('hasBoss', false)->max('enemyForces');
    $chartWidth = ($bars->count() * ($barWidth + $barGap)) - $barGap;
    ?>
    @if( $bars->isNotEmpty() )
        <span class="{{ $graphClass }}">
            {{-- The tooltip anchors to this SVG-width wrapper, not the reserved graph slot, so it
                 centers on the actual bars rather than the empty right-aligned padding. --}}
            <span class="d-inline-flex" data-bs-toggle="tooltip"
                  title="{{ trans_choice($tooltipKey, $pullCount, ['count' => $pullCount]) }}">
                <svg width="{{ $chartWidth }}" height="{{ $chartHeight }}"
                     viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                     role="img" aria-hidden="true" preserveAspectRatio="none">
                    @foreach( $bars as $index => $pull )
                        <?php
                        if ($pull->hasBoss) {
                            $barHeight = $chartHeight;
                        } else {
                            $barHeight = $maxForces > 0
                                ? max($minBar, (int) round(($pull->enemyForces / $maxForces) * $chartHeight))
                                : $minBar;
                        }
                        $x = $index * ($barWidth + $barGap);
                        $y = $chartHeight - $barHeight;
                        ?>
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                              fill="{{ $pull->hasBoss ? $bossFill : $fill }}"></rect>
                    @endforeach
                </svg>
            </span>
        </span>
    @endif
@endif
