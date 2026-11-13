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

@section('content')
    <div class="container">
        <h3>{{ __('view_oauth.authorize.heading', ['client' => $client->name]) }}</h3>
        <p>{{ __('view_oauth.authorize.description', ['client' => $client->name, 'user' => $user->name]) }}</p>

        @if(count($scopes) > 0)
            <p>{{ __('view_oauth.authorize.scopes_intro') }}</p>
            <ul>
                @foreach($scopes as $scope)
                    <li>
                        <code>{{ $scope->id }}</code> -
                        {{ ApiTokenScope::tryFrom($scope->id)?->description() ?? $scope->description }}
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="text-muted">{{ __('view_oauth.authorize.revoke_hint') }}</p>

        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-success">{{ __('view_oauth.authorize.approve') }}</button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-danger">{{ __('view_oauth.authorize.deny') }}</button>
            </form>
        </div>
    </div>
@endsection
