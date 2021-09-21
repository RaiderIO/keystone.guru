<?php
/** @var $user \App\User */
/** @var $link boolean */
/** @var $showAnonIcon boolean */

$link         = $link ?? false;
$showAnonIcon = $showAnonIcon ?? true;
?>

@if($link)
    <a href="{{ route('profile.view', ['user' => $user->id]) }}">
        @endif

        @isset($user->iconfile)
            <img src="{{ $user->iconfile->getURL() }}" alt="{{ __('views/common.user.name.avatar_title') }}"
                 style="max-width: 26px; max-height: 26px"/>
        @elseif($showAnonIcon)
            <i class="fas fa-user"></i>
        @endisset
        {{ $user->name }}

        @if($link)
    </a>
@endif
