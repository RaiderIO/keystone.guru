<?php
/** @var \Illuminate\Support\Collection $floors */
?>
<div class="row no-gutters">
    <div class="col btn-group dropright">
        <button type="button"
                class="btn btn-accent dropdown-toggle {{ $floors->count() > 1 ? '' : 'disabled' }}"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                data-tooltip="tooltip" data-placement="right"
                title="{{ __('views/common.maps.controls.elements.floor_switch.switch_floors') }}">
            <i class="fa fa-dungeon"></i>
        </button>
        <div id="map_floor_selection_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('views/common.maps.controls.elements.floor_switch.floors') }}
            </a>
            @foreach($floors as $floor)
                <a class="dropdown-item {{ $floor->id === $selectedFloorId ? 'active' : '' }}"
                   data-value="{{ $floor->id }}">{{ $floor->name }}</a>
            @endforeach
        </div>
    </div>
</div>