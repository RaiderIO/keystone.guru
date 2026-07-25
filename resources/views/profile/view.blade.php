<?php
/**
 * @var User                            $user
 * @var bool                            $creatorProfileActive
 * @var Collection<int, UserSocialLink> $socialLinks
 * @var Collection<int, DungeonRoute>   $pinnedDungeonRoutes
 * @var int                             $publishedRouteCount
 */

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Models\UserSocialLink;
use Illuminate\Support\Collection;

$creatorProfileActive ??= false;
$socialLinks          ??= collect();
$pinnedDungeonRoutes  ??= collect();
$publishedRouteCount  ??= 0;

$title  = sprintf(__('view_profile.view.title'), $user->name);
$header = sprintf(__('view_profile.view.header'), $user->name);
?>
@extends('layouts.sitepage', [
    'wide' => true,
    'title' => $title,
    'showAds' => false,
    'breadcrumbsParams' => [$user],
])

@include('common.general.inline', ['path' => 'profile/view', 'options' => [
    'dependencies' => ['dungeonroute/table'],
    'user' => $user
]])

@section('header-title')
    {{ $header }}
@endsection

@section('content')
    @if($creatorProfileActive)
        <div class="creator_hero bg-body-tertiary border mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    @if($user->iconfile !== null)
                        <img src="{{ $user->iconfile->getURL() }}"
                             alt="{{ $user->name }}"
                             class="creator_hero_avatar"/>
                    @else
                        <div class="creator_hero_initials bg-secondary text-white" aria-hidden="true">
                            {{ $user->initials }}
                        </div>
                    @endif
                </div>

                <div class="col">
                    <h1 class="creator_hero_name h3">
                        {{ $user->name }}
                    </h1>

                    <div class="text-body-secondary small mb-2">
                        {{ __('view_profile.view.member_since', ['date' => $user->created_at->isoFormat('MMMM YYYY')]) }}
                        &middot;
                        {{ trans_choice('view_profile.view.route_count', $publishedRouteCount, ['count' => $publishedRouteCount]) }}
                    </div>

                    @if(!empty($user->bio))
                        <p class="creator_hero_bio">
                            {{ $user->bio }}
                        </p>
                    @endif
                </div>

                @if($socialLinks->isNotEmpty())
                    <div class="col-12 col-sm-auto">
                        <div class="creator_hero_socials">
                            @foreach($socialLinks as $socialLink)
                                <?php $platformName = __(sprintf('view_profile.view.platform.%s', $socialLink->platform)); ?>
                                <a href="{{ $socialLink->url }}"
                                   class="creator_hero_social_link btn btn-outline-secondary"
                                   target="_blank"
                                   rel="nofollow noopener noreferrer"
                                   title="{{ __('view_profile.view.social_link', ['platform' => $platformName]) }}"
                                   aria-label="{{ __('view_profile.view.social_link', ['platform' => $platformName]) }}">
                                    <i class="{{ $socialLink->getIconClass() }}" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($pinnedDungeonRoutes->isNotEmpty())
            <h2 class="h4 mb-3">
                {{ __('view_profile.view.pinned_routes') }}
            </h2>

            @include('common.dungeonroute.cardlist', [
                'cols' => 3,
                'currentAffixGroup' => null,
                'dungeonroutes' => $pinnedDungeonRoutes,
                'showDungeonImage' => true,
            ])

            <h2 class="h4 mt-4 mb-3">
                {{ __('view_profile.view.all_routes') }}
            </h2>
        @endif
    @endif

    @include('common.dungeonroute.table', ['view' => 'userprofile'])
@endsection
