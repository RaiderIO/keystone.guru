<?php
/**
 * @var User $user
 */
?>
<span data-toggle="tooltip"
      title="{{ $user->name }}">

    <img class="user_icon raider_io_staff_text"
         src="{{ ksgAssetImage('external/raiderio/logo.png') }}"
         alt="{{ __('view_common.user.name.avatar_alt') }}"/>

    {{ __('view_common.user.name.raider_io') }}

    <span class="d-inline d-lg-none">
        - {{ $user->name }}
    </span>
</span>
