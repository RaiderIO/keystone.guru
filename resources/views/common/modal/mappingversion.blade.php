<?php

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * @var MappingVersion|null     $mappingVersion
 * @var Collection<GameVersion> $allGameVersions
 **/

$gameVersionsSelect = $allGameVersions
    ->mapWithKeys(static fn(GameVersion $gameVersion) => [$gameVersion->id => __($gameVersion->name)]);
?>

@include('common.general.inline', ['path' => 'common/dungeon/mappingversion'])

<div class="form-group{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
    {{ html()->label(__('view_admin.dungeon.edit.game_version_id'), 'game_version_id') }}
    <span class="form-required">*</span>
    {{ html()->select('game_version_id', $gameVersionsSelect, $mappingVersion->game_version_id)->id('map_mapping_version_game_version_id')->class('form-control selectpicker') }}
    @include('common.forms.form-error', ['key' => 'game_version_id'])
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.facade_enabled'), 'map_mapping_version_facade_enabled') }}
    {{ html()->checkbox('facade_enabled', $mappingVersion->facade_enabled, 1)->id('map_mapping_version_facade_enabled')->class('form-control left_checkbox') }}
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.enemy_forces_required'), 'map_mapping_version_enemy_forces_required') }}
    {{ html()->number('enemy_forces_required', $mappingVersion->enemy_forces_required)->id('map_mapping_version_enemy_forces_required')->class('form-control') }}
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.enemy_forces_required_teeming'), 'map_mapping_version_enemy_forces_required_teeming') }}
    {{ html()->number('enemy_forces_required_teeming', $mappingVersion->enemy_forces_required_teeming)->id('map_mapping_version_enemy_forces_required_teeming')->class('form-control') }}
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.enemy_forces_shrouded'), 'map_mapping_version_enemy_forces_shrouded') }}
    {{ html()->number('enemy_forces_shrouded', $mappingVersion->enemy_forces_shrouded)->id('map_mapping_version_enemy_forces_shrouded')->class('form-control') }}
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.enemy_forces_shrouded_zul_gamux'), 'map_mapping_version_enemy_forces_shrouded_zul_gamux') }}
    {{ html()->number('enemy_forces_shrouded_zul_gamux', $mappingVersion->enemy_forces_shrouded_zul_gamux)->id('map_mapping_version_enemy_forces_shrouded_zul_gamux')->class('form-control') }}
</div>

<div class="form-group">
    {{ html()->label(__('view_common.modal.mappingversion.timer_max_seconds'), 'map_mapping_version_timer_max_seconds') }}
    {{ html()->number('timer_max_seconds', $mappingVersion->timer_max_seconds)->id('map_mapping_version_timer_max_seconds')->class('form-control') }}
</div>

<div class="form-group">
    <div id="save_mapping_version" class="offset-xl-5 col-xl-2 btn btn-success">
        <i class="fas fa-save"></i> {{ __('view_common.modal.mappingversion.save') }}
    </div>
    <div id="save_mapping_version_saving" class="offset-xl-5 col-xl-2 btn btn-success disabled"
         style="display: none;">
        <i class="fas fa-circle-notch fa-spin"></i>
    </div>
</div>
