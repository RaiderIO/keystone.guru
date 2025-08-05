<?php

use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * @var User $user
 * @var boolean $isRaiderIOStaff
 */
$isRaiderIOStaff ??= false;
?>
<a
    @if($isRaiderIOStaff)
        class="raider_io_staff_text"
    @endif
    href="{{ route('profile.view', ['user' => $user->id]) }}">
    {{ $slot }}
</a>
