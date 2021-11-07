<?php
/** @var $teams \Illuminate\Support\Collection|\App\Models\Team[] */

$id       = $id ?? 'team_id_select';
$name     = $name ?? 'team_id';
$label    = $label ?? __('views/common.team.select.team');
$required = $required ?? true;

$teamsSelect = $teams->pluck('name', 'id')->toArray();
if (!$required) {
    $teamsSelect = [-1 => __('views/common.team.select.select_team')] + $teamsSelect;
}
?>
<div class="form-group">
    @if($label !== false)
        {!! Form::label($name, $label . ($required ? '<span class="form-required">*</span>' : ''), [], false) !!}
    @endif
    {!! Form::select($name, $teamsSelect, null, array_merge(['id' => $id], ['class' => 'form-control selectpicker'])) !!}
</div>
