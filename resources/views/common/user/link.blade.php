<?php

use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * @var User $user
 */
$isRaiderIOStaff = isset($user) && $user->hasRole(Role::ROLE_ADMIN);
?>
<a
    @if($isRaiderIOStaff)
        class="raider_io_staff_text"
    @endif
    href="{{ route('profile.view', ['user' => $user->id]) }}">
    {{ $slot }}
</a>
