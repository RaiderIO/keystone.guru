<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\Season;

/**
 * @var Expansion         $expansion
 * @var Season            $season
 * @var DungeonRoute|null $dungeonroute
 */

$presets = [];
for ($i = 0; $i < $season->presets; ++$i) {
    $presets[$i] = __('view_common.group.affixes.seasonal_index_preset', ['count' => $i + 1]);
}

$shortname = $expansion->shortname;
?>

<div class="form-group {{ $shortname }} presets">
    {!! Form::label('seasonal_index', __('view_common.group.affixes.tormented_preset')) !!}
    <span class="form-required">*</span>
    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            sprintf(__('view_common.group.affixes.tormented_preset_title'), $season->presets)
             }}"></i>
    {!! Form::select('seasonal_index[]', $presets, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
        ['class' => 'form-control selectpicker']) !!}

</div>
