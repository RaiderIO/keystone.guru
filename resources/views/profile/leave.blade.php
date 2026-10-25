<?php

use App\Models\User;
use App\Models\UserSocialLink;

/**
 * @var User           $user
 * @var UserSocialLink $socialLink
 */

$host = $socialLink->getHost();
?>
@extends('layouts.sitepage', [
    'title' => __('view_profile.leave.title'),
    'breadcrumbsParams' => [$user],
])

@section('header-title')
    {{ __('view_profile.leave.title') }}
@endsection

@section('content')
    <div class="profile_leave">
        <p>
            {{ __('view_profile.leave.description', ['name' => $user->name]) }}
        </p>

        <p class="mb-1">
            {{ __('view_profile.leave.destination') }}
        </p>
        <p class="profile_leave_destination">
            <strong class="profile_leave_host">{{ $host }}</strong>
            <code class="d-block text-break">{{ $socialLink->getHref() }}</code>
        </p>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $socialLink->getHref() }}" class="btn btn-primary" rel="nofollow noopener noreferrer">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                {{ __('view_profile.leave.continue', ['host' => $host]) }}
            </a>
            <a href="{{ route('profile.view', ['user' => $user]) }}" class="btn btn-secondary">
                {{ __('view_profile.leave.back', ['name' => $user->name]) }}
            </a>
        </div>
    </div>
@endsection
