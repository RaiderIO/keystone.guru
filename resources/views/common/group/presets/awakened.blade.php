<?php
/** @var $expansion \App\Models\Expansion */
/** @var $season \App\Models\Season */
/** @var $dungeonroute \App\Models\DungeonRoute|null */

$presets = [];
for ($i = 0; $i < $season->presets; $i++) {
    $presets[$i] = __('views/common.group.affixes.seasonal_index_preset', ['count' => $i + 1]);
}
$shortname = $expansion->shortname;
?>

<div class="form-group {{ $shortname }} presets">
    {!! Form::label('seasonal_index', __('views/common.group.affixes.awakened_enemy_set')) !!}
    <span class="form-required">*</span>
    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    __('views/common.group.affixes.awakened_enemy_set_title')
     }}"></i>
    {!! Form::select('seasonal_index[]', $presets, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
        ['class' => 'form-control selectpicker']) !!}
</div>
