@extends('layouts.sitepage', ['rootClass' => 'col-xl-10 offset-xl-1', 'wide' => true, 'title' => __('views/profile.overview.title')])

@section('content')
    @include('common.general.messages')
    <div class="row form-group text-center">
        <div class="col">
            {{ __('Welcome to Keystone.guru! Get started by creating a route, or a new team to collaboratively create routes with your friends.') }}
        </div>
    </div>
    <div class="row form-group text-center">
        <div class="col-md px-4 mt-4">
            <a class="btn btn-outline-success w-100" href="#" data-toggle="modal" data-target="#create_route_modal">
                <h4>
                    <i class="fa fa-plus"></i> {{ __('Create route') }}
                </h4>
                <span class="text-light">
                        {{ __('A route is a path through a dungeon to reach the end goal of 100% enemy forces while killing all bosses.
                                Various tools help you organize your intended path for your party members. You can share them with the world - or keep them private and organize them as you wish. ') }}
                    </span>
            </a>
        </div>

        <div class="col-md px-4 mt-4">
            <a class="btn btn-outline-info w-100" href="{{ route('team.new') }}">
                <h4>
                    <i class="fa fa-plus"></i> {{ __('Create team') }}
                </h4>
                <span class="text-light">
                        {{ __('A team is used to share your routes with friends and help everyone stay in-sync with the latest changes to the routes you do more often.
                                Collaborative editing make adjusting an existing route much easier while you spar for the best route for your team.') }}
                    </span>
            </a>
        </div>
    </div>
    <div class="row form-group">
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


    <h2 class="mt-4">
        {{ __('My routes') }}
    </h2>

    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection
