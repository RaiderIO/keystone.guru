<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRoute> $models
 * @var Collection<int, string>       $publishedStates
 * @var array<string, mixed>          $filters
 * @var bool                          $limited
 */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.dungeonroute.list.title')])

@section('header-title')
    {{ __('view_admin.dungeonroute.list.header') }}
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeonroute_table').on('draw.dt', function () {
                refreshTooltips();
            }).DataTable({
                'aaSorting': [[6, 'desc']],
                'lengthMenu': [50, 100, 200],
            });

            $('#admin_dungeonroute_filters').on('submit', function () {
                return true;
            });

            $('.admin-dungeonroute-delete').on('click', function (e) {
                if (!confirm('{{ __('view_admin.dungeonroute.list.confirm_delete') }}')) {
                    e.preventDefault();
                }
            });

            $('.admin-dungeonroute-copy-mdt').on('click', function () {
                const string = $(this).data('mdt-string');
                navigator.clipboard.writeText(string).then(function () {
                    showSuccessNotification(lang.get('js.copied_to_clipboard'));
                });
            });
        });
    </script>
@endsection

@section('content')
    {{-- Filter form --}}
    <form id="admin_dungeonroute_filters" method="GET" action="{{ route('admin.dungeonroutes') }}" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                @include('common.dungeon.select', [
                    'label'            => __('view_admin.dungeonroute.list.filter_dungeon'),
                    'name'             => 'dungeon_id',
                    'id'               => 'dungeon_id',
                    'required'         => false,
                    'showAll'          => true,
                    'activeOnly'       => false,
                    'ignoreGameVersion' => true,
                    'selected'         => $filters['dungeon_id'] ?? -1,
                ])
            </div>

            <div class="col-md-3 mb-3">
                <label for="published_state_id">{{ __('view_admin.dungeonroute.list.filter_published_state') }}</label>
                <select name="published_state_id" id="published_state_id" class="form-select">
                    <option value="">{{ __('view_admin.dungeonroute.list.filter_all') }}</option>
                    @foreach($publishedStates as $id => $name)
                        <option value="{{ $id }}" @selected($filters['published_state_id'] == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label for="author">{{ __('view_admin.dungeonroute.list.filter_author') }}</label>
                <input type="text" name="author" id="author" class="form-control"
                       value="{{ $filters['author'] }}"
                       placeholder="{{ __('view_admin.dungeonroute.list.filter_author_placeholder') }}">
            </div>

            <div class="col-md-2 mb-3">
                <label for="public_key">{{ __('view_admin.dungeonroute.list.filter_public_key') }}</label>
                <input type="text" name="public_key" id="public_key" class="form-control"
                       value="{{ $filters['public_key'] }}"
                       placeholder="{{ __('view_admin.dungeonroute.list.filter_public_key_placeholder') }}">
            </div>

            <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> {{ __('view_admin.dungeonroute.list.filter_apply') }}
                </button>
            </div>
        </div>
    </form>

    @if($limited)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            {{ __('view_admin.dungeonroute.list.results_limited', ['max' => 500]) }}
        </div>
    @endif

    <table id="admin_dungeonroute_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th>{{ __('view_admin.dungeonroute.list.col_title') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_public_key') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_author') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_dungeon') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_published_state') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_views') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_created_at') }}</th>
            <th>{{ __('view_admin.dungeonroute.list.col_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach($models as $dungeonRoute)
            <tr>
                <td>{{ $dungeonRoute->title }}</td>
                <td>
                    <code>{{ $dungeonRoute->public_key }}</code>
                </td>
                <td>{{ $dungeonRoute->author?->name ?? __('view_admin.dungeonroute.list.no_author') }}</td>
                <td>{{ __($dungeonRoute->dungeon->name) }}</td>
                <td>
                    @php
                        $publishedStateName = $dungeonRoute->publishedState?->name;
                        $publishedStateIcon = match($publishedStateName) {
                            'unpublished'    => 'fa-plane-arrival',
                            'team'           => 'fa-users',
                            'world_with_link' => 'fa-link',
                            'world'          => 'fa-globe',
                            default          => null,
                        };
                        $publishedStateTitle = match($publishedStateName) {
                            'unpublished'    => __('js.publish_state_title_unpublished'),
                            'team'           => __('js.publish_state_title_team'),
                            'world_with_link' => __('js.publish_state_title_world_with_link'),
                            'world'          => __('js.publish_state_title_world'),
                            default          => '-',
                        };
                    @endphp
                    @if($publishedStateIcon)
                        <i class="fas {{ $publishedStateIcon }}"
                           title="{{ $publishedStateTitle }}"
                           data-bs-toggle="tooltip"></i>
                    @else
                        -
                    @endif
                </td>
                <td data-order="{{ $dungeonRoute->views }}">
                    {{ $dungeonRoute->views }}
                    @if($dungeonRoute->rating_count > 0)
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-star"></i> {{ number_format($dungeonRoute->rating, 1) }}
                            ({{ $dungeonRoute->rating_count }})
                        </small>
                    @endif
                </td>
                <td data-order="{{ $dungeonRoute->created_at?->timestamp }}">
                    {{ $dungeonRoute->created_at?->format('Y-m-d') }}
                    @if($dungeonRoute->updated_at && $dungeonRoute->updated_at->ne($dungeonRoute->created_at))
                        <br>
                        <small class="text-muted">{{ $dungeonRoute->updated_at->format('Y-m-d') }}</small>
                    @endif
                </td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('dungeonroute.view', ['dungeon' => $dungeonRoute->dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()]) }}"
                       target="_blank"
                       title="{{ __('view_admin.dungeonroute.list.action_view') }}"
                       data-bs-toggle="tooltip">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a class="btn btn-sm btn-primary"
                       href="{{ route('admin.dungeonroute.edit', ['dungeonRoute' => $dungeonRoute->id]) }}"
                       title="{{ __('view_admin.dungeonroute.list.action_edit') }}"
                       data-bs-toggle="tooltip">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a class="btn btn-sm btn-info"
                       href="{{ route('admin.tools.dungeonroute.view.get', ['dungeonRoute' => $dungeonRoute->id]) }}"
                       target="_blank"
                       title="{{ __('view_admin.dungeonroute.list.action_admin_tools') }}"
                       data-bs-toggle="tooltip">
                        <i class="fas fa-hammer"></i>
                    </a>
                    @if($dungeonRoute->mdtImport->isNotEmpty())
                        <button type="button"
                                class="btn btn-sm btn-secondary admin-dungeonroute-copy-mdt"
                                data-mdt-string="{{ $dungeonRoute->mdtImport->first()->import_string }}"
                                title="{{ __('view_admin.dungeonroute.list.action_copy_mdt') }}"
                                data-bs-toggle="tooltip">
                            <i class="fas fa-copy"></i>
                        </button>
                    @endif
                    <form method="POST"
                          action="{{ route('admin.dungeonroute.delete', ['dungeonRoute' => $dungeonRoute->id]) }}"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-danger admin-dungeonroute-delete"
                                title="{{ __('view_admin.dungeonroute.list.action_delete') }}"
                                data-bs-toggle="tooltip">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
