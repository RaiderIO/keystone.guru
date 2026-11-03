<?php

use App\Service\Notification\Dtos\RenderedNotification;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, RenderedNotification> $notifications
 * @var int|null                              $olderBeforeId
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'col-xl-8 offset-xl-2',
    'wide' => true,
    'title' => __('view_notification.index.title'),
    'showAds' => false,
])

@section('header-title', __('view_notification.index.header'))

@section('content')
    @if($notifications->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-bell-slash"></i> {{ __('view_notification.index.empty') }}
        </div>
    @else
        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="{{ route('notifications.readall') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-check-double"></i> {{ __('view_notification.index.mark_all_read') }}
                </button>
            </form>
        </div>

        <ul class="list-group mb-4">
            @foreach($notifications as $notification)
                <li class="list-group-item {{ $notification->read ? '' : 'border-start border-4 border-warning border-top-0 border-end-0 border-bottom-0' }}">
                    <div class="d-flex align-items-start">
                        <div class="me-3 pt-1">
                            <i class="fas {{ $notification->icon }} fa-fw"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">
                                {{ $notification->title }}
                                @if(!$notification->read)
                                    <span class="badge text-bg-warning rounded-pill">
                                        {{ __('view_notification.index.unread') }}
                                    </span>
                                @endif
                            </div>
                            <div>{!! nl2br(e($notification->body)) !!}</div>
                            <small class="text-muted">{{ $notification->createdAt->diffForHumans() }}</small>
                        </div>
                        <div class="ms-3 text-end text-nowrap">
                            @if($notification->url !== null)
                                <a class="btn btn-sm btn-primary"
                                   href="{{ route('notifications.go', ['notificationMessage' => $notification->notificationMessageId]) }}">
                                    {{ __('view_notification.index.open') }}
                                </a>
                            @elseif(!$notification->read)
                                <form method="POST"
                                      action="{{ route('notifications.read', ['notificationMessage' => $notification->notificationMessageId]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ __('view_notification.index.mark_read') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        @if($olderBeforeId !== null)
            <div class="text-center">
                <a class="btn btn-outline-secondary"
                   href="{{ route('notifications.index', ['before' => $olderBeforeId]) }}">
                    {{ __('view_notification.index.older') }}
                </a>
            </div>
        @endif
    @endif
@endsection
