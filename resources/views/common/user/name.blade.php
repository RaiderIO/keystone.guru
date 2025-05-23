<?php

use App\Models\User;

/**
 * @var User    $user
 * @var boolean $link
 * @var boolean $showAnonIcon
 */

$link         ??= false;
$showAnonIcon ??= true;
?>
@isset($user)
    @if($link)
        <a href="{{ route('profile.view', ['user' => $user->id]) }}">
            @endif

            @isset($user->iconfile)
                <img src="{{ $user->iconfile->getURL() }}" style="max-width: 26px; max-height: 26px; border-radius: 30px;" alt="Icon"/>
            @elseif($showAnonIcon)
                <i class="fas fa-user"></i>
            @endisset
            {{ $user->name }}

            @if($link)
        </a>
    @endif
@endisset
