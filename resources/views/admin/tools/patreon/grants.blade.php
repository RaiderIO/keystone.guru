<?php

use App\Service\Patreon\Dtos\ManualGrantOverviewRow;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, ManualGrantOverviewRow> $manualGrants
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.patreon.grants.title')])

@section('header-title', __('view_admin.tools.patreon.grants.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.patreon.grants.description') }}</p>

    @if($manualGrants->isEmpty())
        <div class="alert alert-success">
            <i class="fas fa-check"></i> {{ __('view_admin.tools.patreon.grants.empty') }}
        </div>
    @else
        <table class="table table-sm table-striped">
            <thead>
            <tr>
                <th>{{ __('view_admin.tools.patreon.grants.column_user') }}</th>
                <th>{{ __('view_admin.tools.patreon.grants.column_email') }}</th>
                <th>{{ __('view_admin.tools.patreon.grants.column_benefits') }}</th>
                <th>{{ __('view_admin.tools.patreon.grants.column_granted_at') }}</th>
                <th>{{ __('view_admin.tools.patreon.grants.column_reason') }}</th>
                <th>{{ __('view_admin.tools.patreon.grants.column_granted_by') }}</th>
                <th class="text-end">{{ __('view_admin.tools.patreon.grants.column_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($manualGrants as $manualGrant)
                <tr>
                    <td>
                        {{ $manualGrant->user->name }}
                        @if($manualGrant->hasRealPatreonLink)
                            <span class="badge text-bg-info rounded-pill"
                                  title="{{ __('view_admin.tools.patreon.grants.real_link_title') }}">
                                {{ __('view_admin.tools.patreon.grants.real_link') }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $manualGrant->user->email }}</td>
                    <td>
                        @if($manualGrant->benefits->isEmpty())
                            <span class="text-muted">{{ __('view_admin.tools.patreon.grants.benefits_none') }}</span>
                        @else
                            {{ $manualGrant->benefits->map(fn($benefit) => __($benefit->name))->implode(', ') }}
                        @endif
                    </td>
                    <td>{{ $manualGrant->grantedAt }}</td>
                    <td>
                        @if($manualGrant->isLegacy)
                            <span class="text-muted fst-italic">
                                {{ __('view_admin.tools.patreon.grants.reason_unknown') }}
                            </span>
                        @else
                            {{ $manualGrant->reason }}
                        @endif
                    </td>
                    <td>
                        {{ $manualGrant->grantedByName ?? __('view_admin.tools.patreon.grants.granted_by_unknown') }}
                    </td>
                    <td class="text-end">
                        <form method="POST"
                              action="{{ route('admin.tools.patreon.grants.revoke', ['user' => $manualGrant->user->id]) }}"
                              style="display: inline;"
                              class="admin-patreon-grant-revoke-confirm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> {{ __('view_admin.tools.patreon.grants.revoke') }}
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
            $('.admin-patreon-grant-revoke-confirm').on('submit', function (e) {
                if (!confirm('{{ __('view_admin.tools.patreon.grants.confirm_revoke') }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
