<?php

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var Collection<Team> $teams
 **/

$id         ??= 'team_id_select';
$name       ??= 'team_id';
$label      ??= __('view_common.team.select.team');
$required   ??= true;
$selectedId ??= null;

$teamsSelect = $teams->pluck('name', 'id')->toArray();
if (!$required) {
    $teamsSelect = [-1 => __('view_common.team.select.select_team')] + $teamsSelect;
}
?>
<div class="form-group">
    @if($label !== false)
        {{ html()->label($label . ($required ? '<span class="form-required">*</span>' : ''), $name) }}
    @endif
    <div class="row">
        @if(!$teams->isEmpty())
            <div class="col">
                {{ html()->select($name, $teamsSelect, $selectedId)->attributes(array_merge(['id' => $id], ['class' => 'form-control selectpicker'])) }}
            </div>
        @endif
        <div class="col-auto">
            <a href="{{ route('team.new') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> {{ __('view_common.team.select.create_team') }}
            </a>
        </div>
    </div>
</div>
