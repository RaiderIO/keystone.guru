<?php
/**
 * @var Dungeon $dungeon
 * @var Floor   $floor
 */

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;

$floorCouplings ??= collect();

$connectedFloorCandidates = $dungeon->floors;
?>
@if($connectedFloorCandidates->isNotEmpty())
    {{ html()->label(__('view_admin.floor.edit.connected_floors'), 'connectedfloors[]')->class('font-weight-bold') }}
    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('view_admin.floor.edit.connected_floors_title')
                 }}"></i>

    <div class="row mb-4">
        <div class="col-2">
            {{ __('view_admin.floor.edit.connected') }}
        </div>
        <div class="col-8">
            {{ __('view_admin.floor.edit.floor_name') }}
        </div>
        <div class="col-2">
            {{ __('view_admin.floor.edit.direction') }}
        </div>
    </div>

    <div class="form-group">
            <?php
        foreach ($connectedFloorCandidates as $connectedFloorCandidate){
            /** @var FloorCoupling $floorCoupling */
            if ($floorCouplings->isNotEmpty()) {
                $floorCoupling = $floorCouplings->where('floor1_id', $floor->id)->where('floor2_id', $connectedFloorCandidate->id)->first();
            }

            $disabled = $connectedFloorCandidate->id === $floor?->id ? ['disabled' => 'disabled'] : [];
            ?>
        <div class="row mb-3">
            <div class="col-2">
                {{ html()->checkbox(sprintf('floor_%s_connected', $connectedFloorCandidate->id), isset($floorCoupling) ? 1 : 0, $connectedFloorCandidate->id)->attributes(array_merge(['class' => 'form-control left_checkbox'], $disabled)) }}
            </div>
            <div class="col-8">
                <a href="{{ route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $connectedFloorCandidate]) }}">{{ __($connectedFloorCandidate->name) }}</a>
            </div>
            <div class="col-2">
                {{ html()->select(sprintf('floor_%s_direction', $connectedFloorCandidate->id), [FloorCoupling::DIRECTION_NONE => __('view_admin.floor.edit.floor_direction.none'), FloorCoupling::DIRECTION_UP => __('view_admin.floor.edit.floor_direction.up'), FloorCoupling::DIRECTION_DOWN => __('view_admin.floor.edit.floor_direction.down'), FloorCoupling::DIRECTION_LEFT => __('view_admin.floor.edit.floor_direction.left'), FloorCoupling::DIRECTION_RIGHT => __('view_admin.floor.edit.floor_direction.right')], isset($floorCoupling) ? $floorCoupling->direction : '')->attributes(array_merge(['class' => 'form-control selectpicker'], $disabled)) }}
            </div>
        </div>
        <?php }
            ?>
    </div>
@endif
