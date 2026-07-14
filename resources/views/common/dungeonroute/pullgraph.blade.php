<?php

use Illuminate\Support\Collection;

/**
 * A compact "route fingerprint": one bar per pull, bar height proportional to the pull's enemy forces,
 * so a viewer instantly gauges the shape of a route (a few big pulls versus many small ones).
 *
 * @var Collection<int, int> $pullForces  Enemy forces per kill zone, ordered by kill zone index.
 * @var int                  $chartHeight Height of the chart in pixels.
 * @var string               $fill        SVG fill color for the bars.
 * @var string               $graphClass  CSS class applied to the wrapping span.
 * @var string               $tooltipKey  Translation key for the "N pulls" tooltip (trans_choice).
 */

$pullCount = $pullForces->count();
?>
@if( $pullCount > 0 )
    <?php
    $barWidth   = 3;
    $barGap     = 1;
    $minBar     = 2;
    // Routes rarely exceed 30 pulls; cap the bar count to keep the graph tidy while the tooltip keeps the true total
    $bars       = $pullForces->take(30)->values();
    $maxForces  = (int) $bars->max();
    $chartWidth = ($bars->count() * ($barWidth + $barGap)) - $barGap;
    ?>
    <span class="{{ $graphClass }}" data-bs-toggle="tooltip"
          title="{{ trans_choice($tooltipKey, $pullCount, ['count' => $pullCount]) }}">
        <svg width="{{ $chartWidth }}" height="{{ $chartHeight }}"
             viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
             role="img" aria-hidden="true" preserveAspectRatio="none">
            @foreach( $bars as $index => $forces )
                <?php
                $barHeight = $maxForces > 0
                    ? max($minBar, (int) round(($forces / $maxForces) * $chartHeight))
                    : $minBar;
                $x = $index * ($barWidth + $barGap);
                $y = $chartHeight - $barHeight;
                ?>
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                      fill="{{ $fill }}"></rect>
            @endforeach
        </svg>
    </span>
@endif
