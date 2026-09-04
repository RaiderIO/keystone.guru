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
            <canvas class="telemetry-chart"
                    data-measurement="{{ $measurement }}"
                    data-name="{{ $name }}"
                    data-axis-label="{{ $axisLabel }}"
                    height="140"></canvas>
        </div>
    </div>
</div>
