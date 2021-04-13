<?php
/** @var \Illuminate\Support\Collection $floors */
?>
<div class="row no-gutters">
    <div class="col btn-group dropright" data-toggle="tooltip" data-placement="right"
         title="{{ __('Switch floors') }}">
        <button type="button"
                class="btn btn-accent dropdown-toggle {{ $floors->count() > 1 ? '' : 'disabled' }}"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-dungeon"></i>
        </button>
        <div id="map_floor_selection_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('Floors') }}
            </a>
            @foreach($floors as $floor)
                <a class="dropdown-item {{ $floor->id === $selectedFloorId ? 'active' : '' }}"
                   data-value="{{ $floor->id }}">{{ $floor->name }}</a>
            @endforeach
        </div>
    </div>
</div>