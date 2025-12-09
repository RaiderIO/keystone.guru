<?php

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use Illuminate\Support\Collection;

/**
 * @var Npc                     $npc
 * @var NpcHealth|null          $npcHealth
 * @var Collection<GameVersion> $allGameVersions
 * @var Collection<Npc>         $npcHealthsAutoComplete
 */

$npcHealth            ??= null;
$existingGameVersions = $npc->npcHealths->keyBy('game_version_id');
$gameVersionsSelect   = $allGameVersions
    ->mapWithKeys(static fn(GameVersion $gameVersion) => [$gameVersion->id => __($gameVersion->name)])
    // When editing, we want the game version of the npc health to be available still
    ->when($npcHealth !== null, static function (Collection $collection) use ($npcHealth, $existingGameVersions) {
        // Remove it from the existing game versions so it can be selected again
        unset($existingGameVersions[$npcHealth->game_version_id]);

        $collection->put($npcHealth->game_version_id, sprintf('%s (%s)',
                __($npcHealth->gameVersion->name),
                __('view_admin.npchealth.edit.current')
            )
        );
    })
    ->except($existingGameVersions->keys());
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc, $npcHealth],
    'showAds' => false,
    'title' => __('view_admin.npchealth.edit.title', ['name' => __($npc->name)]),
])
@section('header-title')
    {{ __('view_admin.npchealth.edit.header', ['name' => __($npc->name)]) }}
@endsection

@include('common.general.inline', ['path' => 'admin/npchealth/edit', 'options' => [
    'healthSelector' => '#health',
    'scaledHealthSelector' => '#scaled_health',
    'scaledHealthToBaseHealthApplyBtnSelector' => '#scaled_health_to_health_apply_btn',
    'scaledHealthPercentageSelector' => '#scaled_health_percentage',
    'scaledHealthLevelSelector' => '#scaled_health_level',
    'scaledHealthTypeSelector' => '#scaled_health_type',
    'healthPercentageSelector' => '#percentage',
]])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_npc_npc_health_table').DataTable({
                'order': [[3, 'desc']],
                // Amount per page
                'pageLength': 25,
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
            });

            $('.apply-health').on('click', function (e) {
                e.preventDefault();
                const health = $(this).data('health');
                $('#health').val(health);
            });
        });
    </script>
@endsection

@section('content')
    @isset($npcHealth)
        {{ html()->modelForm($npc, 'PATCH', route('admin.npc.npchealth.update', [$npc, $npcHealth]))->attribute('autocomplete', 'off')->open() }}
    @else
        {{ html()->modelForm($npc, 'POST', route('admin.npc.npchealth.savenew', $npc))->attribute('autocomplete', 'off')->open() }}
    @endisset

    <div class="form-group{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npchealth.edit.game_version'), 'game_version_id') }}
        <span class="form-required">*</span>
        {{ html()->select('game_version_id', $gameVersionsSelect, $npcHealth?->game_version_id)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'game_version_id'])
    </div>


    <div class="form-group{{ $errors->has('health') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npchealth.edit.health'), 'health') }}
        <span class="form-required">*</span>
        <div class="row">
            <div class="col-3">
                {{ html()->number('health', $npcHealth?->health)->id('health')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'health'])
            </div>
            <div class="col-9">
                <div class="row">
                    <div class="col-auto">
                        <div id="scaled_health_to_health_apply_btn" class="btn btn-info">
                            {{ __('view_admin.npchealth.edit.scaled_health_to_health_apply') }}
                        </div>
                    </div>
                    <div class="col">
                        {{ html()->text('scaled_health')->id('scaled_health')->class('form-control')->placeholder(__('view_admin.npchealth.edit.scaled_health_placeholder')) }}
                    </div>
                    <div class="col">
                        {{ html()->text('scaled_health_percentage')->id('scaled_health_percentage')->class('form-control')->placeholder(__('view_admin.npchealth.edit.scaled_health_percentage_placeholder')) }}
                    </div>
                    <div class="col">
                        {{ html()->text('scaled_health_level')->id('scaled_health_level')->class('form-control')->style('display: none;') }}
                    </div>
                    <div class="col">
                        {{ html()->select('scaled_health_type', ['none' => __('view_admin.npchealth.edit.scaled_type_none'), 'fortified' => __('view_admin.npchealth.edit.scaled_type_fortified', ['affix' => __('affixes.fortified.name')]), 'tyrannical' => __('view_admin.npchealth.edit.scaled_type_tyrannical', ['affix' => __('affixes.tyrannical.name')])])->id('scaled_health_type')->class('form-control selectpicker') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group{{ $errors->has('percentage') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npchealth.edit.percentage'), 'percentage') }}
        {{ html()->number('percentage', $npcHealth?->percentage ?? 100)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'percentage'])
    </div>

    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.npchealth.edit.submit'))->class('btn btn-info')->name('submit') }}
    </div>

    {{ html()->closeModelForm() }}

    @if($npcHealth !== null)
        <div class="form-group">
            {{ html()->label(__('view_admin.npchealth.edit.auto_complete_npc_healths'), 'admin_npc_npc_health_table') }}

            <table id="admin_npc_npc_health_table" class="tablesorter default_table table-striped">
                <thead>
                <tr>
                    <th width="10%">{{ __('view_admin.npchealth.edit.table_header.npc_id') }}</th>
                    <th width="40%">{{ __('view_admin.npchealth.edit.table_header.npc_name') }}</th>
                    <th width="10%">{{ __('view_admin.npchealth.edit.table_header.classification') }}</th>
                    <th width="10%">{{ __('view_admin.npchealth.edit.table_header.health') }}</th>
                    <th width="10%">{{ __('view_admin.npchealth.edit.table_header.percentage') }}</th>
                    <th width="20%">{{ __('view_admin.npchealth.edit.table_header.actions') }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach($npcHealthsAutoComplete as $autoCompleteNpc)
                    @if(($autoCompleteNpcHealth = $autoCompleteNpc->getHealthByGameVersion($npcHealth->gameVersion)) && $autoCompleteNpc->id !== $npc->id)
                        <tr>
                            <td>{{ __($autoCompleteNpc->id) }}</td>
                            <td>{{ __($autoCompleteNpc->name) }}</td>
                            <td>{{ __($autoCompleteNpc->classification->name) }}</td>
                            <td>{{ number_format($autoCompleteNpcHealth->health) }}</td>
                            <td>{{ $autoCompleteNpcHealth->percentage }}</td>
                            <td>
                                <div class="row no-gutters">
                                    <div class="col">
                                        <a class="btn btn-success apply-health"
                                           data-health="{{ $autoCompleteNpcHealth->health }}">
                                            <i class="fas fa-check"></i>&nbsp;{{ __('view_admin.npchealth.edit.apply_to_npc_health') }}
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <a class="btn btn-info"
                                           href="{{ route('admin.npc.npchealth.edit', ['npc' => $autoCompleteNpc, 'npcHealth' => $autoCompleteNpcHealth]) }}">
                                            <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.npchealth.edit.edit_npc_health') }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>

            </table>
        </div>
    @endif
@endsection
