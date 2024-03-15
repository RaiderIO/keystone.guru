<?php
/** @var $mappingVersion App\Models\Mapping\MappingVersion|null */
?>

@include('common.general.inline', ['path' => 'common/dungeon/mappingversion'])

<div class="form-group">
    <label for="map_mapping_version_enemy_forces_required">
        {{ __('view_common.modal.mappingversion.enemy_forces_required') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::number('enemy_forces_required', $mappingVersion->enemy_forces_required,
                ['id' => 'map_mapping_version_enemy_forces_required', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="map_mapping_version_enemy_forces_required_teeming">
        {{ __('view_common.modal.mappingversion.enemy_forces_required_teeming') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::number('enemy_forces_required_teeming', $mappingVersion->enemy_forces_required_teeming,
                ['id' => 'map_mapping_version_enemy_forces_required_teeming', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="map_mapping_version_enemy_forces_shrouded">
        {{ __('view_common.modal.mappingversion.enemy_forces_shrouded') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::number('enemy_forces_shrouded', $mappingVersion->enemy_forces_shrouded,
                ['id' => 'map_mapping_version_enemy_forces_shrouded', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="map_mapping_version_enemy_forces_shrouded_zul_gamux">
        {{ __('view_common.modal.mappingversion.enemy_forces_shrouded_zul_gamux') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::number('enemy_forces_shrouded_zul_gamux', $mappingVersion->enemy_forces_shrouded_zul_gamux,
                ['id' => 'map_mapping_version_enemy_forces_shrouded_zul_gamux', 'class' => 'form-control']) !!}
        </div>
    </div>
</div>

<div class="form-group">
    <label for="map_mapping_version_timer_max_seconds">
        {{ __('view_common.modal.mappingversion.timer_max_seconds') }}
    </label>
    <div class="row">
        <div class="col">
            {!! Form::number('timer_max_seconds', $mappingVersion->timer_max_seconds,
                ['id' => 'map_mapping_version_timer_max_seconds', 'class' => 'form-control']) !!}
        </div>
    </div>
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
