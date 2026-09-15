<?php
/**
 * @var string      $chartTitle
 * @var string      $measurement
 * @var string|null $name
 * @var string      $axisLabel
 */
?>
<div class="col-12 col-xl-6 mb-4">
    <div class="card h-100">
        <div class="card-header">{{ $chartTitle }}</div>
        <div class="card-body">
            {{-- Chart.js sizes the canvas to its parent, so the parent is what fixes the plot height --}}
            <div style="height: 260px;">
                <canvas class="telemetry-chart"
                        data-measurement="{{ $measurement }}"
                        data-name="{{ $name }}"
                        data-axis-label="{{ $axisLabel }}"></canvas>
            </div>
        </div>
    </div>
</div>
