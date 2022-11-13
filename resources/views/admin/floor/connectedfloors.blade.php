<?php
/** @var $dungeon \App\Models\Dungeon */
/** @var $floor \App\Models\Floor */
$floorCouplings = $floorCouplings ?? collect();

$connectedFloorCandidates = $dungeon->floors;
if (isset($floor)) {
    $connectedFloorCandidates = $connectedFloorCandidates->except(optional($floor)->id);
}
?>
@if($connectedFloorCandidates->isNotEmpty())
    {!! Form::label('connectedfloors[]', __('views/admin.floor.edit.connected_floors'), ['class' => 'font-weight-bold']) !!}
    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('views/admin.floor.edit.connected_floors_title')
                 }}"></i>

    <div class="row mb-4">
        <div class="col-2">
            {{ __('views/admin.floor.edit.connected') }}
        </div>
        <div class="col-8">
            {{ __('views/admin.floor.edit.floor_name') }}
        </div>
        <div class="col-2">
            {{ __('views/admin.floor.edit.direction') }}
        </div>
    </div>

    <div class="form-group">
            <?php
        foreach ($connectedFloorCandidates as $connectedFloorCandidate){
            /** @var \App\Models\FloorCoupling $floorCoupling */
            if ($floorCouplings->isNotEmpty()) {
                $floorCoupling = $floorCouplings->where('floor1_id', $floor->id)->where('floor2_id', $connectedFloorCandidate->id)->first();
            }
            ?>
        <div class="row mb-3">
            <div class="col-2">
                {!! Form::checkbox(sprintf('floor_%s_connected', $connectedFloorCandidate->id),
                    $connectedFloorCandidate->id, isset($floorCoupling) ? 1 : 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
            <div class="col-8">
                <a href="{{ route('admin.floor.edit', ['dungeon' => $dungeon, 'floor' => $connectedFloorCandidate]) }}">{{ __($connectedFloorCandidate->name) }}</a>
            </div>
            <div class="col-2">
                {!! Form::select(sprintf('floor_%s_direction', $connectedFloorCandidate->id), [
                            \App\Models\FloorCoupling::DIRECTION_NONE => __('views/admin.floor.edit.floor_direction.none'),
                            \App\Models\FloorCoupling::DIRECTION_UP => __('views/admin.floor.edit.floor_direction.up'),
                            \App\Models\FloorCoupling::DIRECTION_DOWN => __('views/admin.floor.edit.floor_direction.down'),
                            \App\Models\FloorCoupling::DIRECTION_LEFT => __('views/admin.floor.edit.floor_direction.left'),
                            \App\Models\FloorCoupling::DIRECTION_RIGHT => __('views/admin.floor.edit.floor_direction.right')
                        ], isset($floorCoupling) ? $floorCoupling->direction : '', ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <?php } ?>
    </div>
@endif
