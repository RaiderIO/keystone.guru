<?php

use App\Http\Requests\AdminToolsNotificationStoreRequest;
use App\Models\Laratrust\Role;
use App\Models\Notification\NotificationMessage;
use Illuminate\Database\Eloquent\Collection;

/**
 * @var Collection<int, NotificationMessage> $notificationMessages
 * @var Collection<int, Role>                $roles
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.notifications.list.title')])

@section('header-title', __('view_admin.tools.notifications.list.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.notifications.list.description') }}</p>

    <div class="card mb-4">
        <div class="card-header">{{ __('view_admin.tools.notifications.list.form_header') }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.tools.notifications.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="body">{{ __('view_admin.tools.notifications.list.column_body') }}</label>
                    <textarea class="form-control" id="body" name="body" rows="4" maxlength="1000"
                              required>{{ old('body') }}</textarea>
                    <small class="text-muted">{{ __('view_admin.tools.notifications.list.body_help') }}</small>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="audience">{{ __('view_admin.tools.notifications.list.column_audience') }}</label>
                        <select class="form-control" id="audience" name="audience">
                            <option value="{{ AdminToolsNotificationStoreRequest::AUDIENCE_USERS }}"
                                    @selected(old('audience') === AdminToolsNotificationStoreRequest::AUDIENCE_USERS)>
                                {{ __('view_admin.tools.notifications.list.audience_users') }}
                            </option>
                            <option value="{{ AdminToolsNotificationStoreRequest::AUDIENCE_ROLE }}"
                                    @selected(old('audience') === AdminToolsNotificationStoreRequest::AUDIENCE_ROLE)>
                                {{ __('view_admin.tools.notifications.list.audience_role') }}
                            </option>
                            <option value="{{ AdminToolsNotificationStoreRequest::AUDIENCE_EVERYONE }}"
                                    @selected(old('audience') === AdminToolsNotificationStoreRequest::AUDIENCE_EVERYONE)>
                                {{ __('view_admin.tools.notifications.list.audience_everyone') }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="user_names">{{ __('view_admin.tools.notifications.list.column_user_names') }}</label>
                        <input type="text" class="form-control" id="user_names" name="user_names"
                               value="{{ old('user_names') }}" placeholder="Wotuu, SomeoneElse">
                        <small class="text-muted">{{ __('view_admin.tools.notifications.list.user_names_help') }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="role">{{ __('view_admin.tools.notifications.list.column_role') }}</label>
                        <select class="form-control" id="role" name="role">
                            <option value="">-</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                    {{ $role->display_name ?? $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-1 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="url">{{ __('view_admin.tools.notifications.list.column_url') }}</label>
                        <input type="text" class="form-control" id="url" name="url" value="{{ old('url') }}"
                               placeholder="/discover">
                        <small class="text-muted">{{ __('view_admin.tools.notifications.list.url_help') }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="expires_at">{{ __('view_admin.tools.notifications.list.column_expires_at') }}</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at"
                               value="{{ old('expires_at') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-bullhorn"></i> {{ __('view_admin.tools.notifications.list.submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-secondary">
        <i class="fas fa-info-circle"></i> {{ __('view_admin.tools.notifications.list.email_note') }}
    </div>

    @if($notificationMessages->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> {{ __('view_admin.tools.notifications.list.empty') }}
        </div>
    @else
        <table class="table table-sm table-striped">
            <thead>
            <tr>
                <th>{{ __('view_admin.tools.notifications.list.column_type') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_body') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_audience') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_delivered') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_read') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_created_by') }}</th>
                <th>{{ __('view_admin.tools.notifications.list.column_created_at') }}</th>
                <th class="text-end">{{ __('view_admin.tools.notifications.list.column_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($notificationMessages as $notificationMessage)
                <tr class="{{ $notificationMessage->isExpired() ? 'text-muted' : '' }}">
                    <td><code>{{ $notificationMessage->type->value }}</code></td>
                    <td>{{ Str::limit($notificationMessage->body ?? '', 80) }}</td>
                    <td>{{ $notificationMessage->audience->value }}</td>
                    <td>
                        @if($notificationMessage->isGlobal())
                            {{ __('view_admin.tools.notifications.list.delivered_everyone') }}
                        @else
                            {{ $notificationMessage->delivered_count }}
                        @endif
                    </td>
                    <td>{{ $notificationMessage->read_count }}</td>
                    <td>{{ $notificationMessage->createdBy?->name ?? '-' }}</td>
                    <td>{{ $notificationMessage->created_at }}</td>
                    <td class="text-end">
                        <form method="POST"
                              action="{{ route('admin.tools.notifications.destroy', ['notificationMessage' => $notificationMessage->id]) }}"
                              style="display: inline;"
                              class="admin-notification-delete-confirm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> {{ __('view_admin.tools.notifications.list.retract') }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('.admin-notification-delete-confirm').on('submit', function (e) {
                if (!confirm('{{ __('view_admin.tools.notifications.list.confirm_retract') }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
