@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var \App\Service\Season\SeasonService $seasonService */
$currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();
$teeming = old('teeming') ?? $currentAffixGroup->isTeeming();
$defaultSelectedAffixes = old('affixes') ?? [$currentAffixGroup->id];
?>

@extends('layouts.app', ['wide' => true, 'title' => __('New route')])
@section('header-title', $headerTitle)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div class="container {{ isset($model) ? 'hidden' : '' }}">
        <h3>
            {{ __('General') }}
        </h3>
        <div class="form-group">
            <label for="dungeon_route_title">
                {{ __('Title') }}<span class="form-required">*</span>
                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('Choose a title that will uniquely identify the route for you over other similar routes you may create.')
                 }}"></i>
            </label>
            {!! Form::text('dungeon_route_title', '', ['class' => 'form-control']) !!}
        </div>
        @include('common.dungeon.select', ['id' => 'dungeon_id_select', 'showAll' => false, 'showSiegeWarning' => true])

        <div class="form-group">
            <label for="teeming">
                {{ __('Teeming') }}
                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('Check to change the dungeon to resemble Teeming week.')
                 }}"></i>
            </label>
            {!! Form::checkbox('teeming', 1, $teeming, ['id' => 'teeming', 'class' => 'form-control left_checkbox']) !!}
        </div>

{{--        <div class="form-group">--}}
{{--            <label for="template">--}}
{{--                {{ __('Template') }}--}}
{{--                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{--}}
{{--                __('Check to pre-fill the route with a template route (the route\'s demo). This gives you straight forward scaffolding to base your route on.')--}}
{{--                 }}"></i>--}}
{{--            </label>--}}
{{--            {!! Form::checkbox('template', 1, 0, ['class' => 'form-control left_checkbox']) !!}--}}
{{--        </div>--}}
        <h3>
            {{ __('Affixes') }} <span class="form-required">*</span>
        </h3>

        @include('common.group.affixes', ['teemingselector' => '#teeming', 'defaultSelected' => $defaultSelectedAffixes])

        @include('common.dungeonroute.attributes')

        <h3>
            {{ __('Group composition') }}
        </h3>
        @include('common.group.composition')


        @if(Auth::user()->hasPaidTier(\App\Models\PaidTier::UNLISTED_ROUTES))
            <h3>
                {{ __('Sharing') }}
            </h3>
            <div class="form-group">
                <label for="unlisted">
                    {{ __('Private') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('When checked, only people with the link can view your route. It will not be listed in the Routes page.')
                 }}"></i>
                </label>
                {!! Form::checkbox('unlisted', 1, 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
        @endif

        @if(Auth::user()->hasRole('admin'))
            <h3>
                {{ __('Admin') }}
            </h3>
            <div class="form-group">
                {!! Form::label('demo', __('Demo route')) !!}
                {!! Form::checkbox('demo', 1, 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
        @endif

        <div class="col-lg-12">
            <div class="form-group">
                {!! Form::submit(__('Create route'), ['class' => 'btn btn-info col-md-auto']) !!}
            </div>
        </div>
        @if(!Auth::user()->hasPaidTier('unlimited-routes'))
            {{ sprintf(__('You may create %s more route(s).'),  Auth::user()->getRemainingRouteCount()) }}

            <a href="https://www.patreon.com/keystoneguru">
                <i class="fab fa-patreon"></i> {{ __('Patrons have no limits!') }}
            </a>
        @endif
    </div>

    {!! Form::close() !!}
@endsection

