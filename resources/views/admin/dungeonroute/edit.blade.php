<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Support\Collection;

/**
 * @var DungeonRoute            $dungeonRoute
 * @var Collection<int, string> $publishedStates
 */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.dungeonroute.edit.title')])

@section('header-title')
    {{ __('view_admin.dungeonroute.edit.header') }}
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('.admin-dungeonroute-delete-confirm').on('click', function (e) {
                if (!confirm('{{ __('view_admin.dungeonroute.list.confirm_delete') }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection

@section('content')
    <div class="mb-4">
        {{-- Route info (read-only) --}}
        <div class="card mb-4">
            <div class="card-header">
                {{ __('view_admin.dungeonroute.edit.section_info') }}
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_title') }}</dt>
                    <dd class="col-sm-9">{{ $dungeonRoute->title }}</dd>

                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_public_key') }}</dt>
                    <dd class="col-sm-9">
                        <code>{{ $dungeonRoute->public_key }}</code>
                        <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonRoute->dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()]) }}"
                           target="_blank" class="ml-2">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </dd>

                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_author') }}</dt>
                    <dd class="col-sm-9">{{ $dungeonRoute->author?->name ?? __('view_admin.dungeonroute.list.no_author') }}</dd>

                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_dungeon') }}</dt>
                    <dd class="col-sm-9">{{ __($dungeonRoute->dungeon->name) }}</dd>

                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_created_at') }}</dt>
                    <dd class="col-sm-9">{{ $dungeonRoute->created_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-3">{{ __('view_admin.dungeonroute.edit.label_views') }}</dt>
                    <dd class="col-sm-9">{{ $dungeonRoute->views }}</dd>
                </dl>
            </div>
        </div>

        {{-- Update published state --}}
        <div class="card mb-4">
            <div class="card-header">
                {{ __('view_admin.dungeonroute.edit.section_published_state') }}
            </div>
            <div class="card-body">
                {{ html()->modelForm($dungeonRoute, 'PATCH', route('admin.dungeonroute.update', ['dungeonRoute' => $dungeonRoute->id]))->open() }}

                <div class="form-group{{ $errors->has('published_state_id') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.dungeonroute.edit.label_published_state'), 'published_state_id') }}
                    {{ html()->select('published_state_id', $publishedStates, $dungeonRoute->published_state_id)->class('form-control') }}
                    @include('common.forms.form-error', ['key' => 'published_state_id'])
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('view_admin.dungeonroute.edit.save') }}
                </button>

                {{ html()->form()->close() }}
            </div>
        </div>

        {{-- Actions --}}
        <div class="card mb-4">
            <div class="card-header">
                {{ __('view_admin.dungeonroute.edit.section_actions') }}
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                {{-- Claim this route --}}
                <form method="POST" action="{{ route('admin.dungeonroute.claim', ['dungeonRoute' => $dungeonRoute->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-check"></i> {{ __('view_admin.dungeonroute.edit.claim') }}
                    </button>
                </form>

                {{-- Admin tools shortcut --}}
                <a href="{{ route('admin.tools.dungeonroute.view.get', ['dungeonRoute' => $dungeonRoute->id]) }}"
                   target="_blank" class="btn btn-info">
                    <i class="fas fa-hammer"></i> {{ __('view_admin.dungeonroute.edit.admin_tools') }}
                </a>

                {{-- Delete --}}
                <form method="POST" action="{{ route('admin.dungeonroute.delete', ['dungeonRoute' => $dungeonRoute->id]) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger admin-dungeonroute-delete-confirm">
                        <i class="fas fa-trash"></i> {{ __('view_admin.dungeonroute.edit.delete') }}
                    </button>
                </form>
            </div>
        </div>

        <a href="{{ route('admin.dungeonroutes') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('view_admin.dungeonroute.edit.back_to_list') }}
        </a>
    </div>
@endsection
