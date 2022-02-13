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

    <h3>
        {{ __('Route coverage') }}
    </h3>
    <div class="row form-group mb-4">
        <div class="col">
            @include('common.dungeonroute.coverage.affixgroup')
        </div>
    </div>

    <h3>
        {{ __('Route overview') }}
    </h3>
    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection
