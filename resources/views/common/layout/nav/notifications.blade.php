<?php
/**
 * @var int $numUnreadNotifications
 */
?>
@auth
    @feature(\App\Features\Notifications::class)
        <ul class="navbar-nav flex-row">
            <li class="nav-item dropdown notification_bell">
                <button type="button" class="nav-link notification_bell_toggle position-relative" id="notificationBellToggle"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                        data-notification-bell-url="{{ route('notifications.popover') }}">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ __('view_common.layout.nav.notifications.title') }}</span>
                    <span class="notification_bell_badge badge rounded-pill text-bg-warning {{ $numUnreadNotifications > 0 ? '' : 'd-none' }}"
                          data-notification-bell-badge>
                        <span data-notification-bell-count>{{ $numUnreadNotifications > 99 ? '99+' : $numUnreadNotifications }}</span>
                        <span class="visually-hidden">{{ __('view_common.layout.nav.notifications.unread') }}</span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification_bell_menu" aria-labelledby="notificationBellToggle">
                    <h6 class="dropdown-header">{{ __('view_common.layout.nav.notifications.title') }}</h6>
                    <div data-notification-bell-list aria-live="polite">
                        <p class="dropdown-item-text notification_bell_note text-center small mb-0 py-2">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> {{ __('view_common.layout.nav.notifications.loading') }}
                        </p>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                        {{ __('view_common.layout.nav.notifications.show_all') }}
                    </a>
                </div>
            </li>
        </ul>
    @endfeature
@endauth
