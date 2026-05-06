<?php
/**
 * @var Expansion         $expansion
 * @var Season            $season
 * @var DungeonRoute|null $dungeonroute
 */

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\Season;

$presets = [];
for ($i = 0; $i < $season->presets; ++$i) {
    $presets[$i] = __('view_common.group.affixes.seasonal_index_preset', ['count' => $i + 1]);
}

$shortname = $expansion->shortname;
?>

<div class="form-group {{ $shortname }} presets">
    {{ html()->label(__('view_common.group.affixes.awakened_enemy_set'), 'seasonal_index') }}
    <span class="form-required">*</span>
    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    __('view_common.group.affixes.awakened_enemy_set_title')
     }}"></i>
    {{ html()->select('seasonal_index[]', $presets, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0)->class('form-control selectpicker') }}
</div>
