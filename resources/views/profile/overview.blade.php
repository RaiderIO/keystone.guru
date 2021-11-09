@extends('layouts.sitepage', ['rootClass' => 'col-xl-10 offset-xl-1', 'wide' => true, 'title' => __('views/profile.overview.title')])

@section('content')
    @include('common.general.messages')
    @if( Auth::user()->dungeonroutes()->count() === 0)
        <div class="row form-group text-center">
            <div class="col">
                {{ __('views/profile.overview.welcome_text') }}
            </div>
        </div>
    @endif
    <div class="row form-group text-center">
        <div class="col-md px-4 mt-4">
            <a class="btn btn-outline-success w-100" href="#" data-toggle="modal" data-target="#create_route_modal">
                <h4>
                    <i class="fa fa-plus"></i> {{ __('views/profile.overview.create_route') }}
                </h4>
                <span class="text-light">
                        {{ __('views/profile.overview.create_route_description') }}
                    </span>
            </a>
        </div>

        <div class="col-md px-4 mt-4">
            <a class="btn btn-outline-info w-100" href="{{ route('team.new') }}">
                <h4>
                    <i class="fa fa-plus"></i> {{ __('views/profile.overview.create_team') }}
                </h4>
                <span class="text-light">
                        {{ __('views/profile.overview.create_team_description') }}
                    </span>
            </a>
        </div>
    </div>
    <div class="row form-group mb-4">
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.favorites') }}">
                <i class="fa fa-star"></i> {{ __('views/profile.overview.favorites') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag"></i> {{ __('views/profile.overview.tags') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('team.list') }}">
                <i class="fa fa-users"></i> {{ __('views/profile.overview.teams') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.edit') }}">
                <i class="fa fa-user"></i> {{ __('views/profile.overview.profile') }}
            </a>
        </div>
    </div>

    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection
