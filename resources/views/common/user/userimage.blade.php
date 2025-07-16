<?php
/**
 * @var User    $user
 * @var boolean $showAnonIcon
 */
?>

@isset($user->iconfile)
    <img class="user_icon"
         src="{{ $user->iconfile->getURL() }}"
         alt="{{ __('view_common.user.name.avatar_alt') }}"/>
@elseif($showAnonIcon)
    <i class="fas fa-user"></i>
@endisset

{{ $user->name }}
