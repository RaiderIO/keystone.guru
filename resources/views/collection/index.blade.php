<?php

use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRouteCollection> $dungeonRouteCollections
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.index.title')])

@section('header-title', __('view_collection.index.header'))
@section('header-addition')
    <a href="{{ route('collections.new') }}" class="btn btn-success text-white float-end" role="button">
        <i class="fas fa-plus"></i> {{ __('view_collection.index.create_collection') }}
    </a>
@endsection

@section('content')
    @include('common.general.messages')

    <p class="text-body-secondary">
        {{ __('view_collection.index.description') }}
    </p>

    @if($dungeonRouteCollections->isEmpty())
        <div class="card">
            <div class="card-body text-center">
                {{ __('view_collection.index.no_collections') }}
            </div>
        </div>
    @else
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th width="40%">{{ __('view_collection.index.table_header_name') }}</th>
                <th width="15%">{{ __('view_collection.index.table_header_category') }}</th>
                <th width="20%">{{ __('view_collection.index.table_header_visibility') }}</th>
                <th width="10%">{{ __('view_collection.index.table_header_routes') }}</th>
                <th width="15%"></th>
            </tr>
            </thead>

            <tbody>
            @foreach($dungeonRouteCollections as $dungeonRouteCollection)
                <tr>
                    <td>
                        <a href="{{ route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}">
                            {{ $dungeonRouteCollection->name }}
                        </a>
                    </td>
                    <td>
                        @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
                            {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
                        @else
                            <span class="text-body-secondary">
                                {{ __('view_collection.index.no_category') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        {{ __(sprintf('view_collection.published_state.%s', $dungeonRouteCollection->getPublishedStateName())) }}
                        @if($dungeonRouteCollection->team !== null)
                            ({{ $dungeonRouteCollection->team->name }})
                        @endif
                    </td>
                    <td>
                        {{ $dungeonRouteCollection->dungeon_route_collection_routes_count }}
                    </td>
                    <td>
                        <a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
                           class="float-end">
                            <i class="fas fa-external-link-alt"></i> {{ __('view_collection.index.view') }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
