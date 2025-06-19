<?php

use App\Models\CharacterClass;
use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @var User                         $user
 * @var Collection<CharacterClass>   $allClasses
 * @var Collection<GameServerRegion> $allRegions
 */

$user      = Auth::getUser();
$isOAuth   = $user->password === '';
$menuItems = [
    ['icon' => 'fa-user', 'text' => __('view_profile.edit.profile'), 'target' => '#profile'],
    ['icon' => 'fa-cog', 'text' => __('view_profile.edit.account'), 'target' => '#account'],
    ['icon' => 'fab fa-patreon', 'text' => __('view_profile.edit.patreon'), 'target' => '#patreon'],
];
// Optionally add this menu item
if (!$isOAuth) {
    $menuItems[] = ['icon' => 'fa-key', 'text' => __('view_profile.edit.change_password'), 'target' => '#change-password'];
}

$menuItems[] = ['icon' => 'fa-user-secret', 'text' => __('view_profile.edit.privacy'), 'target' => '#privacy'];
$menuItems[] = ['icon' => 'fa-flag', 'text' => __('view_profile.edit.reports'), 'target' => '#reports'];

$menuTitle = sprintf(__('view_profile.edit.menu_title'), $user->name);
?>
@extends('layouts.sitepage', [
    'wide' => true,
    'title' => __('view_profile.edit.title'),
    'showAds' => false,
    'menuTitle' => $menuTitle,
    'menuItems' => $menuItems,
    'menuModelEdit' => $user,
])

@include('common.general.inline', ['path' => 'profile/edit', 'options' => [

]])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            // Code for base app
            var appCode = _inlineManager.getInlineCode('layouts/app');
            appCode._newPassword('#new_password');

            // Disabled since it's not shown by default and causes a JS error otherwise
            // $('#user_reports_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <div class="tab-content">
        @include('profile.edittabs.profile', ['user' => $user])

        @include('profile.edittabs.account', ['user' => $user])

        @include('profile.edittabs.patreon', ['user' => $user])

        @if(!$isOAuth)
            @include('profile.edittabs.changepassword', ['user' => $user])
        @endif

        @include('profile.edittabs.privacy', ['user' => $user])
    </div>
@endsection
