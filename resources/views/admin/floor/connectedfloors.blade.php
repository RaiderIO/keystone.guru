<?php
/**
 * @var \App\Models\Dungeon     $dungeon
 * @var \App\Models\Floor\Floor $floor
 */
$floorCouplings ??= collect();

$connectedFloorCandidates = $dungeon->floors;
?>
@if($connectedFloorCandidates->isNotEmpty())
    {!! Form::label('connectedfloors[]', __('view_admin.floor.edit.connected_floors'), ['class' => 'font-weight-bold']) !!}
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
            /** @var \App\Models\Floor\FloorCoupling $floorCoupling */
            if ($floorCouplings->isNotEmpty()) {
                $floorCoupling = $floorCouplings->where('floor1_id', $floor->id)->where('floor2_id', $connectedFloorCandidate->id)->first();
            }

            $disabled = $connectedFloorCandidate->id === $floor?->id ? ['disabled' => 'disabled'] : [];
            ?>
        <div class="row mb-3">
            <div class="col-2">
                {!! Form::checkbox(sprintf('floor_%s_connected', $connectedFloorCandidate->id),
                    $connectedFloorCandidate->id, isset($floorCoupling) ? 1 : 0,
                    array_merge(['class' => 'form-control left_checkbox'], $disabled)) !!}
            </div>
            <div class="col-8">
                <a href="{{ route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $connectedFloorCandidate]) }}">{{ __($connectedFloorCandidate->name) }}</a>
            </div>
            <div class="col-2">
                {!! Form::select(sprintf('floor_%s_direction', $connectedFloorCandidate->id), [
                            \App\Models\Floor\FloorCoupling::DIRECTION_NONE => __('view_admin.floor.edit.floor_direction.none'),
                            \App\Models\Floor\FloorCoupling::DIRECTION_UP => __('view_admin.floor.edit.floor_direction.up'),
                            \App\Models\Floor\FloorCoupling::DIRECTION_DOWN => __('view_admin.floor.edit.floor_direction.down'),
                            \App\Models\Floor\FloorCoupling::DIRECTION_LEFT => __('view_admin.floor.edit.floor_direction.left'),
                            \App\Models\Floor\FloorCoupling::DIRECTION_RIGHT => __('view_admin.floor.edit.floor_direction.right')
                        ], isset($floorCoupling) ? $floorCoupling->direction : '', array_merge(['class' => 'form-control selectpicker'], $disabled)) !!}
            </div>
        </div>
        <?php }
            ?>
    </div>
@endif
