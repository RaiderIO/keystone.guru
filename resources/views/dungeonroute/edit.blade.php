@extends('layouts.app', ['custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $model->title])
<?php
/** @var $model \App\Models\DungeonRoute */
?>
@include('common.general.inline', ['path' => 'dungeonroute/edit'])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id)->load('floors'),
            'dungeonroute' => $model,
            'edit' => true
        ])

        @include('common.maps.editsidebar', [
            'show' => [
                'shareable-link' => true,
                'route-settings' => true,
                'route-publish' => true
            ]
        ])
    </div>

    <!-- Modal settings -->
    <div class="modal fade" id="settings_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg vertical-align-center">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    <i class="fas fa-times"></i>
                </button>
                <div class="probootstrap-modal-flex">
                    <div class="probootstrap-modal-content">

                        <div id='settings' class='col-lg-12'>
                            <h3>
                                {{ __('General') }}
                            </h3>
                            <div class='form-group'>
                                <label for="dungeon_route_title">
                                    {{ __('Title') }}*
                                    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                                __('Choose a title that will uniquely identify the route for you over other similar routes you may create.')
                                 }}"></i>
                                </label>
                                {!! Form::text('dungeon_route_title', $model->title, ['id' => 'dungeon_route_title', 'class' => 'form-control']) !!}
                            </div>
                            @include('common.dungeonroute.attributes', ['dungeonroute' => $model])

                            <h3>
                                {{ __('Group composition (optional)') }}
                            </h3>

                            @php($factions = $model->dungeon->isSiegeOfBoralus() ? \App\Models\Faction::where('name', '<>', 'Unspecified')->get() : null)
                            @include('common.group.composition', ['dungeonroute' => $model, 'factions' => $factions])

                            <h3 class='mt-1'>
                                {{ __('Affixes (optional)') }}
                            </h3>

                            <div class='container mt-1'>
                                {!! Form::checkbox('teeming', 1, $model->teeming, ['id' => 'teeming', 'class' => 'form-control left_checkbox d-none']) !!}
                                @include('common.group.affixes', ['dungeonroute' => $model, 'teemingselector' => '#teeming'])
                            </div>

                            @if(Auth::user()->hasPaidTier('unlisted-routes') )
                                <h3>
                                    {{ __('Sharing') }}
                                </h3>
                                <div class='form-group'>
                                    {!! Form::label('unlisted', __('Private (when checked, only people with the link can view your route)')) !!}
                                    {!! Form::checkbox('unlisted', 1, $model->unlisted, ['class' => 'form-control left_checkbox']) !!}
                                </div>
                            @endif

                            @if(Auth::user()->hasRole('admin'))
                                <h3>
                                    {{ __('Admin') }}
                                </h3>
                                <div class='form-group'>
                                    {!! Form::label('demo', __('Demo route')) !!}
                                    {!! Form::checkbox('demo', 1, $model->demo, ['class' => 'form-control left_checkbox']) !!}
                                </div>
                            @endif

                            <div class='form-group'>
                                <div id='save_settings' class='offset-lg-5 col-lg-2 btn btn-success'>
                                    <i class='fas fa-save'></i> {{ __('Save settings') }}
                                </div>
                                <div id='save_settings_saving' class='offset-lg-5 col-lg-2 btn btn-success disabled'
                                     style='display: none;'>
                                    <i class='fas fa-circle-notch fa-spin'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

