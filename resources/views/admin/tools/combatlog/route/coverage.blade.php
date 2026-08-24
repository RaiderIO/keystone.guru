@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.route.coverage.title')])

@section('header-title', __('view_admin.tools.combatlog.route.coverage.header'))

@section('content')
    <p class="text-muted">
        {{ __('view_admin.tools.combatlog.route.coverage.description') }}
        <a href="{{ route('admin.tools.combatlog.route.enemy_failures.view') }}">
            {{ __('view_admin.tools.combatlog.route.coverage.enemy_failures_link') }}
        </a>
    </p>

    <form method="GET" action="{{ route('admin.tools.combatlog.route.coverage.view') }}" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label for="days" class="form-label">{{ __('view_admin.tools.combatlog.route.coverage.days') }}</label>
            <input type="number" class="form-control" id="days" name="days" min="1" max="365" value="{{ $days }}"/>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">
                {{ __('view_admin.tools.combatlog.route.coverage.refresh') }}
            </button>
        </div>
        @if($season !== null)
            <div class="col text-end text-muted">
                {{ __('view_admin.tools.combatlog.route.coverage.season', ['season' => $season->name_long]) }}
            </div>
        @endif
    </form>

    @if($season === null)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ __('view_admin.tools.combatlog.route.coverage.no_season') }}
        </div>
    @else
        <table class="table table-sm table-striped align-middle">
            <thead>
            <tr>
                <th>{{ __('view_admin.tools.combatlog.route.coverage.column_dungeon') }}</th>
                <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.column_routes') }}</th>
                <th class="text-end text-danger">{{ __('view_admin.tools.combatlog.route.coverage.column_critical') }}</th>
                <th class="text-end text-warning">{{ __('view_admin.tools.combatlog.route.coverage.column_warning') }}</th>
                <th class="text-end text-success">{{ __('view_admin.tools.combatlog.route.coverage.column_ok') }}</th>
                <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.column_over') }}</th>
                <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.column_unknown') }}</th>
                <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.column_problem_share') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($dungeons as $row)
                @php($collapseId = sprintf('dungeon_routes_%d', $row['dungeon']->id))
                <tr data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" style="cursor: pointer;">
                    <td>
                        <i class="fas fa-caret-right"></i> {{ __($row['dungeon']->name) }}
                    </td>
                    <td class="text-end">{{ $row['total'] }}</td>
                    <td class="text-end {{ $row['buckets']['critical'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                        {{ $row['buckets']['critical'] }}
                    </td>
                    <td class="text-end {{ $row['buckets']['warning'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['buckets']['warning'] }}
                    </td>
                    <td class="text-end {{ $row['buckets']['ok'] > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $row['buckets']['ok'] }}
                    </td>
                    <td class="text-end {{ $row['buckets']['over'] > 0 ? '' : 'text-muted' }}">
                        {{ $row['buckets']['over'] }}
                    </td>
                    <td class="text-end {{ $row['buckets']['unknown'] > 0 ? '' : 'text-muted' }}">
                        {{ $row['buckets']['unknown'] }}
                    </td>
                    <td class="text-end">{{ $row['problemPercentage'] }}%</td>
                </tr>
                <tr class="collapse" id="{{ $collapseId }}">
                    <td colspan="8" class="bg-body-tertiary">
                        @if($row['routes']->isEmpty())
                            <span class="text-muted">{{ __('view_admin.tools.combatlog.route.coverage.no_routes') }}</span>
                        @else
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.detail_percentage') }}</th>
                                    <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.detail_enemy_forces') }}</th>
                                    <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.detail_enemy_failures') }}</th>
                                    <th class="text-end">{{ __('view_admin.tools.combatlog.route.coverage.detail_level') }}</th>
                                    <th>{{ __('view_admin.tools.combatlog.route.coverage.detail_created_at') }}</th>
                                    <th>{{ __('view_admin.tools.combatlog.route.coverage.detail_links') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($row['routes'] as $route)
                                    @php($dungeonRoute = $route['dungeonRoute'])
                                    <tr>
                                        <td class="text-end {{ ['critical' => 'text-danger fw-bold', 'warning' => 'text-warning', 'ok' => 'text-success', 'over' => '', 'unknown' => 'text-muted'][$route['bucket']] }}">
                                            {{ $route['percentage'] === null ? '?' : sprintf('%.2f%%', $route['percentage']) }}
                                        </td>
                                        <td class="text-end text-muted">
                                            {{ $dungeonRoute->enemy_forces }} / {{ $route['enemyForcesRequired'] }}
                                        </td>
                                        <td class="text-end {{ $route['enemyFailureCount'] > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ $route['enemyFailureCount'] }}
                                        </td>
                                        <td class="text-end">{{ $route['level'] ?? '-' }}</td>
                                        <td>
                                            {{ $route['createdAt']?->format('Y-m-d H:i') ?? '-' }}
                                            @if($route['duplicate'])
                                                <span class="badge bg-secondary">
                                                    {{ __('view_admin.tools.combatlog.route.coverage.detail_duplicate') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a target="_blank"
                                               href="{{ route('dungeonroute.view', ['dungeon' => $dungeonRoute->dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()]) }}">
                                                {{ __('view_admin.tools.combatlog.route.coverage.detail_view_route') }}
                                            </a>
                                            &middot;
                                            <a target="_blank"
                                               href="{{ route('admin.tools.dungeonroute.view.get', ['dungeonRoute' => $dungeonRoute->id]) }}">
                                                {{ __('view_admin.tools.combatlog.route.coverage.detail_view_admin') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @if($row['hiddenRouteCount'] > 0)
                                <small class="text-muted">
                                    {{ __('view_admin.tools.combatlog.route.coverage.showing_worst', ['shown' => $row['routes']->count(), 'total' => $row['total']]) }}
                                </small>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
