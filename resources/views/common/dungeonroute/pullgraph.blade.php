<?php

use Illuminate\Support\Collection;

/**
 * A compact "route fingerprint": one bar per pull, bar height proportional to the pull's enemy forces,
 * so a viewer instantly gauges the shape of a route (a few big pulls versus many small ones).
 * Boss pulls render as full-height accent bars, turning the graph into a timeline of trash profile
 * plus boss milestones. Pulls that grant no enemy forces and contain no boss are omitted entirely.
 *
 * @var Collection<int, stdClass> $pullForces  Enemy forces (int) + boss flag (bool) per kill zone, ordered by kill zone index.
 * @var int                                                        $chartHeight Height of the chart in pixels.
 * @var string                                                     $fill        SVG fill color for regular (trash) bars.
 * @var string                                                     $bossFill    SVG fill color for full-height boss bars.
 * @var string                                                     $graphClass  CSS class applied to the wrapping span.
 * @var string                                                     $tooltipKey  Translation key for the "N pulls" tooltip (trans_choice).
 */

// The tooltip always reflects the route's real pull count, regardless of how many bars survive filtering
$pullCount = $pullForces->count();
?>
@if( $pullCount > 0 )
    <?php
    $barWidth = 3;
    $barGap   = 1;
    $minBar   = 2;
    // Zero-forces pulls without a boss carry no information - drop them before capping so they
    // neither render as noise nor eat into the 30-bar budget
    $bars = $pullForces
        ->filter(static fn(stdClass $pull) => $pull->enemy_forces > 0 || $pull->has_boss)
        // Routes rarely exceed 30 pulls; cap the bar count to keep the graph tidy while the tooltip keeps the true total
        ->take(30)
        ->values();
    // Trash bars normalize against the largest trash pull; boss bars are always full height
    $maxForces  = (int) $bars->where('has_boss', false)->max('enemy_forces');
    $chartWidth = ($bars->count() * ($barWidth + $barGap)) - $barGap;
    ?>
    @if( $bars->isNotEmpty() )
        <span class="{{ $graphClass }}" data-bs-toggle="tooltip"
              title="{{ trans_choice($tooltipKey, $pullCount, ['count' => $pullCount]) }}">
            <svg width="{{ $chartWidth }}" height="{{ $chartHeight }}"
                 viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                 role="img" aria-hidden="true" preserveAspectRatio="none">
                @foreach( $bars as $index => $pull )
                    <?php
                    if ($pull->has_boss) {
                        $barHeight = $chartHeight;
                    } else {
                        $barHeight = $maxForces > 0
                            ? max($minBar, (int) round(($pull->enemy_forces / $maxForces) * $chartHeight))
                            : $minBar;
                    }
                    $x = $index * ($barWidth + $barGap);
                    $y = $chartHeight - $barHeight;
                    ?>
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                          fill="{{ $pull->has_boss ? $bossFill : $fill }}"></rect>
                @endforeach
            </svg>
        </span>
    @endif
@endif
