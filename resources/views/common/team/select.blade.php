<?php
/** @var $teams \Illuminate\Support\Collection|\App\Models\Team[] */

$id       = $id ?? 'team_id_select';
$name     = $name ?? 'team_id';
$label    = $label ?? __('views/common.team.select.team');
$required = $required ?? true;
$selectedId = $selectedId ?? null;

$teamsSelect = $teams->pluck('name', 'id')->toArray();
if (!$required) {
    $teamsSelect = [-1 => __('views/common.team.select.select_team')] + $teamsSelect;
}
?>
<div class="form-group">
    @if($label !== false)
        {!! Form::label($name, $label . ($required ? '<span class="form-required">*</span>' : ''), [], false) !!}
    @endif
    <div class="row">
        @if(!$teams->isEmpty())
            <div class="col">
                {!! Form::select($name, $teamsSelect, $selectedId, array_merge(['id' => $id], ['class' => 'form-control selectpicker'])) !!}
            </div>
        @endif
        <div class="col-auto">
            <a href="{{ route('team.new') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> {{ __('views/common.team.select.create_team') }}
            </a>
        </div>
    </div>
</div>
