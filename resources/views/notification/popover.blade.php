<?php

use App\Service\Notification\Dtos\RenderedNotification;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, RenderedNotification> $notifications
 * @var int                                   $unreadCount
 */
?>
<div data-notification-bell-unread-count="{{ $unreadCount }}">
    @if($notifications->isEmpty())
        <p class="dropdown-item-text notification_bell_note text-center small mb-0 py-2">
            <i class="fas fa-bell-slash" aria-hidden="true"></i> {{ __('view_notification.popover.empty') }}
        </p>
    @else
        @foreach($notifications as $notification)
            @php($itemClass = sprintf('dropdown-item notification_bell_item %s', $notification->read ? '' : 'notification_bell_item_unread'))
            @if($notification->url !== null)
                <form method="POST" class="m-0"
                      action="{{ route('notifications.go', ['notificationMessage' => $notification->notificationMessageId]) }}">
                    @csrf
                    <button type="submit" class="{{ $itemClass }} text-start">
            @else
                <a class="{{ $itemClass }}" href="{{ route('notifications.index') }}">
            @endif
                <span class="d-flex align-items-start gap-2">
                    <i class="fas {{ $notification->icon }} fa-fw mt-1" aria-hidden="true"></i>
                    <span class="flex-grow-1 notification_bell_item_text">
                        <span class="d-block fw-bold">
                            {{ $notification->title }}
                            @if(!$notification->read)
                                <span class="visually-hidden">({{ __('view_notification.popover.unread') }})</span>
                            @endif
                        </span>
                        <span class="d-block notification_bell_item_body">{{ $notification->body }}</span>
                        <small class="d-block notification_bell_item_time">{{ $notification->createdAt->diffForHumans() }}</small>
                    </span>
                </span>
            @if($notification->url !== null)
                    </button>
                </form>
            @else
                </a>
            @endif
        @endforeach
    @endif
</div>
