@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
/** @var \App\Service\Season\SeasonService $seasonService */
$currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();
$teeming = old('teeming') ?? $currentAffixGroup->isTeeming();
$defaultSelectedAffixes = old('affixes') ?? [$currentAffixGroup->id];
?>

@isset($model)
    {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
@else
    {{ Form::open(['route' => 'dungeonroute.savenew']) }}
@endisset
<div class="container">
    @if( !isset($model) )
        @include('common.dungeon.select', ['id' => 'dungeon_id_select', 'showAll' => false, 'showSiegeWarning' => true])
    @endif

    @auth
        <div class="form-group">
            <label for="dungeon_route_title">
                {{ __('Title') }}<span class="form-required">*</span>
                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('Choose a title that will uniquely identify the route for you over other similar routes you may create. The title may be visible to others once you choose to publish your route.')
                 }}"></i>
            </label>
            {!! Form::text('dungeon_route_title', '', ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            <label for="dungeon_route_sandbox">
                {{ __('Temporary route') }}
                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __(
                    sprintf('A temporary route will not show up in your profile and will be deleted automatically after %d hours',
                    config('keystoneguru.sandbox_dungeon_route_expires_hours'))
                )
                 }}"></i>
            </label>
            {!! Form::checkbox('dungeon_route_sandbox', 1, false, ['class' => 'form-control left_checkbox']) !!}
        </div>

        <div class="form-group">
            <div id="accordion">
                <div class="card">
                    <div class="card-header" id="headingOne">
                        <h5 class="mb-0">
                            <a href="#" class="btn btn-link" data-toggle="collapse" data-target="#collapseOne"
                               aria-expanded="false" aria-controls="collapseOne">
                                {{ __('Advanced options') }}
                            </a>
                        </h5>
                    </div>

                    <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="card-body">

                            <h3>
                                {{ __('Affixes') }} <span class="form-required">*</span>
                            </h3>

                            @include('common.group.affixes', ['teemingselector' => '#teeming', 'defaultSelected' => $defaultSelectedAffixes])

                            @include('common.dungeonroute.attributes')

                            <h3>
                                {{ __('Group composition') }}
                            </h3>
                            @include('common.group.composition')

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
    @endauth

    <div class="col-lg-12">
        <div class="form-group">
            {!! Form::submit(__('Create route'), ['class' => 'btn btn-info col-md-auto']) !!}
        </div>
    </div>
</div>

{!! Form::close() !!}
