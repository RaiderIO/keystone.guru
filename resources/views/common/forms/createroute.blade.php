@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
/** @var \App\Service\Season\SeasonService $seasonService */
$currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();
$teeming = old('teeming') ?? $currentAffixGroup->isTeeming();
$defaultSelectedAffixes = old('affixes') ?? [$currentAffixGroup->id];

// Make sure $model exists
$dungeonroute = $dungeonroute ?? null;
?>

@if(!isset($dungeonroute))
    {{ Form::open(['route' => 'dungeonroute.savenew']) }}
@endisset
<div class="container">
    @if( !isset($dungeonroute) )
        @include('common.dungeon.select', ['id' => 'dungeon_id_select', 'showAll' => false, 'showSiegeWarning' => true])
    @endif

    <div class="form-group">
        <label for="dungeon_route_title">
            {{ __('Title') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('Choose a title that will uniquely identify the route for you over other similar routes you may create. The title may be visible to others once you choose to publish your route.')
             }}"></i>
        </label>
        {!! Form::text('dungeon_route_title', optional($dungeonroute)->title ?? '', ['id' => 'dungeon_route_title', 'class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        <div id="accordion">
            <div class="card">
                <div class="card-header" id="headingOne">
                    <h5 class="mb-0">
                        <a href="#" class="btn btn-link" data-toggle="collapse"
                           data-target="#createRouteAdvancedCollapse"
                           aria-expanded="false" aria-controls="createRouteAdvancedCollapse">
                            {{ __('Advanced options') }}
                        </a>
                    </h5>
                </div>

                <div id="createRouteAdvancedCollapse" class="collapse" aria-labelledby="headingOne"
                     data-parent="#accordion">
                    <div class="card-body">
                        <h3>
                            {{ __('Affixes') }} <span class="form-required">*</span>
                        </h3>

                        @include('common.group.affixes', [
                            'dungeonroute'     => $dungeonroute ?? null,
                            'teemingSelector'  => '#teeming',
                            'collapseSelector' => '#createRouteAdvancedCollapse',
                            'defaultSelected'  => $defaultSelectedAffixes
                            ])

                        @include('common.dungeonroute.attributes')

                        <h3>
                            {{ __('Group composition') }}
                        </h3>
                        @include('common.group.composition', [
                            'collapseSelector' => '#createRouteAdvancedCollapse',
                            'dungeonroute'     => $dungeonroute ?? null,
                            ])

                        @if(Auth::check() && Auth::user()->hasRole('admin'))
                            <h3>
                                {{ __('Admin') }}
                            </h3>
                            <div class="form-group">
                                {!! Form::label('demo', __('Demo route')) !!}
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
                {!! Form::submit(__('Create route'), ['class' => 'btn btn-info col-md-auto']) !!}
            </div>
        </div>
    @endif
</div>

@if(!isset($dungeonroute))
    {!! Form::close() !!}
@else
    <div class="form-group">
        <div id="save_route_settings" class="offset-xl-5 col-xl-2 btn btn-success">
            <i class="fas fa-save"></i> {{ __('Save settings') }}
        </div>
        <div id="save_route_settings_saving" class="offset-xl-5 col-xl-2 btn btn-success disabled"
             style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i>
        </div>
    </div>
@endif