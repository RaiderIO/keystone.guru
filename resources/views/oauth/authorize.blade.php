<?php

use App\Models\User;
use App\Service\User\ApiTokenScope;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;

/**
 * @var Client             $client
 * @var User               $user
 * @var array<int, Scope>  $scopes
 * @var string             $authToken
 * @var \Illuminate\Http\Request $request
 */
?>
@extends('layouts.sitepage', [
    'title' => __('view_oauth.authorize.title'),
    'showAds' => false,
])
@section('header-title', __('view_oauth.authorize.heading', ['client' => $client->name]))

@section('content')
    <div class="container text-center">
        <h1 class="text-primary">
            <i class="fas fa-plug"></i>
        </h1>
        <p>
            {{ __('view_oauth.authorize.description', ['client' => $client->name, 'user' => $user->name]) }}
        </p>

        @if(count($scopes) > 0)
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="card text-start mb-3">
                        <div class="card-body">
                            <p class="mb-2">{{ __('view_oauth.authorize.scopes_intro') }}</p>
                            <ul class="list-unstyled mb-0">
                                @foreach($scopes as $scope)
                                    <li class="d-flex py-1">
                                        <i class="fas fa-check text-success me-2 mt-1"></i>
                                        <div>
                                            <div>{{ ApiTokenScope::tryFrom($scope->id)?->description() ?? $scope->description }}</div>
                                            <code class="small">{{ $scope->id }}</code>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <p class="text-muted small">{{ __('view_oauth.authorize.revoke_hint') }}</p>

        <div class="row">
            <div class="col">
                <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" class="btn btn-primary col-lg-auto">
                        <i class="fas fa-check"></i> {{ __('view_oauth.authorize.approve') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" class="btn btn-outline-danger col-lg-auto">
                        <i class="fas fa-times"></i> {{ __('view_oauth.authorize.deny') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
