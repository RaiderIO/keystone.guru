<?php

use App\Models\BannedIpAddress;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, BannedIpAddress> $bannedIpAddresses
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.bannedipaddresses.list.title')])

@section('header-title', __('view_admin.tools.bannedipaddresses.list.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.bannedipaddresses.list.description') }}</p>

    <div class="card mb-4">
        <div class="card-header">{{ __('view_admin.tools.bannedipaddresses.list.form_header') }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.tools.bannedipaddresses.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" for="ip_address">{{ __('view_admin.tools.bannedipaddresses.list.column_ip_address') }}</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address"
                               value="{{ old('ip_address') }}" placeholder="1.2.3.4 or 1.2.3.0/24" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="reason">{{ __('view_admin.tools.bannedipaddresses.list.column_reason') }}</label>
                        <input type="text" class="form-control" id="reason" name="reason" value="{{ old('reason') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="expires_at">{{ __('view_admin.tools.bannedipaddresses.list.column_expires_at') }}</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-ban"></i> {{ __('view_admin.tools.bannedipaddresses.list.submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($bannedIpAddresses->isEmpty())
        <div class="alert alert-success">
            <i class="fas fa-check"></i> {{ __('view_admin.tools.bannedipaddresses.list.empty') }}
        </div>
    @else
        <table class="table table-sm table-striped">
            <thead>
            <tr>
                <th>{{ __('view_admin.tools.bannedipaddresses.list.column_ip_address') }}</th>
                <th>{{ __('view_admin.tools.bannedipaddresses.list.column_reason') }}</th>
                <th>{{ __('view_admin.tools.bannedipaddresses.list.column_expires_at') }}</th>
                <th>{{ __('view_admin.tools.bannedipaddresses.list.column_created_by') }}</th>
                <th>{{ __('view_admin.tools.bannedipaddresses.list.column_created_at') }}</th>
                <th class="text-end">{{ __('view_admin.tools.bannedipaddresses.list.column_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($bannedIpAddresses as $bannedIpAddress)
                <tr class="{{ $bannedIpAddress->isExpired() ? 'text-muted' : '' }}">
                    <td><code>{{ $bannedIpAddress->ip_address }}</code></td>
                    <td>{{ $bannedIpAddress->reason }}</td>
                    <td>
                        @if($bannedIpAddress->expires_at === null)
                            {{ __('view_admin.tools.bannedipaddresses.list.expires_never') }}
                        @elseif($bannedIpAddress->isExpired())
                            <span class="badge text-bg-secondary rounded-pill">
                                {{ __('view_admin.tools.bannedipaddresses.list.status_expired') }}
                            </span>
                        @else
                            {{ $bannedIpAddress->expires_at }}
                        @endif
                    </td>
                    <td>{{ $bannedIpAddress->createdBy?->name ?? __('view_admin.tools.bannedipaddresses.list.created_by_unknown') }}</td>
                    <td>{{ $bannedIpAddress->created_at }}</td>
                    <td class="text-end">
                        <form method="POST"
                              action="{{ route('admin.tools.bannedipaddresses.destroy', ['bannedIpAddress' => $bannedIpAddress->id]) }}"
                              style="display: inline;"
                              class="admin-bannedipaddress-delete-confirm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> {{ __('view_admin.tools.bannedipaddresses.list.remove') }}
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
            $('.admin-bannedipaddress-delete-confirm').on('submit', function (e) {
                if (!confirm('{{ __('view_admin.tools.bannedipaddresses.list.confirm_remove') }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
