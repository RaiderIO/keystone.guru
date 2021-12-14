<?php
/** @var \App\Models\DungeonRoute|null $dungeonroute */
$teeming = old('teeming') ?? false;
$defaultSelectedAffixes = old('affixes') ?? [];

// Make sure $model exists
$dungeonroute = $dungeonroute ?? null;
$dungeonSelectId = 'dungeon_id_select';
?>

@if(!isset($dungeonroute))
    {{ Form::open(['route' => 'dungeonroute.savenew']) }}
@endisset
<div class="container">
    @if( !isset($dungeonroute) )
        @include('common.dungeon.select', ['id' => $dungeonSelectId, 'showAll' => false, 'showSiegeWarning' => true])
    @else
        <input id="{{ $dungeonSelectId }}" type="hidden" value="{{ $dungeonroute->dungeon_id }}">
    @endif

    @include('common.team.select', ['required' => false])

    <div class="form-group">
        <label for="dungeon_route_title">
            {{ __('views/common.forms.createroute.title') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('views/common.forms.createroute.title_title')
             }}"></i>
        </label>
        {!! Form::text('dungeon_route_title', optional($dungeonroute)->title ?? '', ['id' => 'dungeon_route_title', 'class' => 'form-control']) !!}
    </div>
    <?php // The user does not really know a description for his/her route when creating it, so hide it. It will be available from the route settings ?>
    @isset($dungeonroute)
        <div class="form-group">
            <label for="dungeon_route_description">
                {{ __('views/common.forms.createroute.description') }}
                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('views/common.forms.createroute.description_title')
             }}"></i>
            </label>
            {!! Form::textarea('dungeon_route_description', $dungeonroute->description ?? '', ['id' => 'dungeon_route_description', 'class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            <label for="dungeon_route_level">
                {{ __('views/common.forms.createroute.key_levels') }}
                <i class="fas fa-info-circle" data-toggle="tooltip"
                   title="{{ __('views/common.forms.createroute.key_levels_title') }}"></i>
            </label>
            {!! Form::text('dungeon_route_level', sprintf('%s;%s', $dungeonroute->level_min, $dungeonroute->level_max) ?? '',
                ['id' => 'dungeon_route_level', 'class' => 'form-control', 'style' => 'display: none;']) !!}
        </div>
    @endisset

    <div class="form-group">
        <div id="create_route">
            <div class="card">
                <div class="card-header" id="create_route_heading">
                    <h5 class="mb-0">
                        <a href="#" class="btn btn-link" data-toggle="collapse"
                           data-target="#create_route_advanced_collapse"
                           aria-expanded="false" aria-controls="create_route_advanced_collapse">
                            {{ __('views/common.forms.createroute.advanced_options') }}
                        </a>
                    </h5>
                </div>

                <div id="create_route_advanced_collapse" class="collapse" aria-labelledby="create_route_heading"
                     data-parent="#create_route">
                    <div class="card-body">

                        <p>{{ __('views/common.forms.createroute.affixes') }} <span class="form-required">*</span></p>

                        @include('common.group.affixes', [
                            'dungeonroute'     => $dungeonroute ?? null,
                            'dungeonSelector' => sprintf('#%s', $dungeonSelectId),
                            'teemingSelector'  => '#teeming',
                            'collapseSelector' => '#createRouteAdvancedCollapse',
                            'defaultSelected'  => $defaultSelectedAffixes
                            ])

                        @include('common.dungeonroute.attributes')

                        <p>{{ __('views/common.forms.createroute.group_composition') }}</p>
                        <div class="form-group">
                            @include('common.group.composition', [
                                'collapseSelector' => '#createRouteAdvancedCollapse',
                                'dungeonroute'     => $dungeonroute ?? null,
                                ])
                        </div>

                        @if(Auth::check() && Auth::user()->hasRole('admin'))
                            <h3>
                                {{ __('views/common.forms.createroute.admin') }}
                            </h3>
                            <div class="form-group">
                                {!! Form::label('demo', __('views/common.forms.createroute.demo_route')) !!}
                                {!! Form::checkbox('demo', 1, 0, ['class' => 'form-control left_checkbox']) !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!isset($dungeonroute))
        <div class="col-lg-12">
            <div class="form-group">
                {!! Form::submit(__('views/common.forms.createroute.create_route'), ['class' => 'btn btn-info col-md-auto']) !!}
            </div>
        </div>
    @endif
</div>

@if(!isset($dungeonroute))
    {!! Form::close() !!}
@else
    <div class="form-group">
        <div id="save_route_settings" class="offset-xl-5 col-xl-2 btn btn-success">
            <i class="fas fa-save"></i> {{ __('views/common.forms.createroute.save_settings') }}
        </div>
        <div id="save_route_settings_saving" class="offset-xl-5 col-xl-2 btn btn-success disabled"
             style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i>
        </div>
    </div>
@endif
