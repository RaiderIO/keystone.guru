<?php
$heatmapHotspotsEnabled = (bool)($_COOKIE['heatmap_hotspots_enabled'] ?? 1);
?>
@include('common.general.inline', ['path' => 'common/maps/heatmaphotspots', 'options' => [
    'toggleSelector' => '#heatmap_hotspots_toggle',
    'enabledStateCookie' => 'heatmap_hotspots_enabled',
    'dependencies' => ['common/maps/map'],
]])

<div class="d-flex align-items-center justify-content-end">
    <label class="me-2 mb-0" for="heatmap_hotspots_toggle">
        {{ __('view_common.maps.controls.heatmaphotspots.deadly_npcs') }}
    </label>
    <input id="heatmap_hotspots_toggle" type="checkbox"
           {{ $heatmapHotspotsEnabled ? 'checked' : '' }}
           data-toggle="toggle" data-width="100px" data-height="20px"
           data-onstyle="primary" data-offstyle="primary"
           data-on="{{ __('view_common.maps.controls.heatmaphotspots.shown') }}"
           data-off="{{ __('view_common.maps.controls.heatmaphotspots.hidden') }}">
</div>
