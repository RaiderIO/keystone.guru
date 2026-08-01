<?php

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @var LengthAwarePaginator<int, User>                        $creators
 * @var string|null                                            $search
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 * @var DungeonRouteCollectionCategory|null                    $selectedCategory
 */

$categories       ??= collect();
$selectedCategory ??= null;
?>
@extends('layouts.sitepage', [
    'wide'  => true,
    'title' => __('view_creator.directory.title'),
])

@section('header-title')
    {{ __('view_creator.directory.header') }}
@endsection

@section('content')
    <p class="text-body-secondary">
        {{ __('view_creator.directory.description') }}
    </p>

    <form method="GET" action="{{ route('creators.index') }}" class="row g-2 mb-4" role="search">
        <div class="col-12 col-md-6 col-lg-4">
            <label for="creator_search" class="visually-hidden">
                {{ __('view_creator.directory.search_label') }}
            </label>
            <div class="input-group">
                <input type="search"
                       id="creator_search"
                       name="search"
                       class="form-control"
                       maxlength="24"
                       value="{{ $search }}"
                       placeholder="{{ __('view_creator.directory.search_placeholder') }}"/>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('view_creator.directory.search_submit') }}
                </button>
            </div>
            @include('common.forms.form-error', ['key' => 'search'])
        </div>

        @if($categories->isNotEmpty())
            <div class="col-12 col-md-4 col-lg-3">
                <label for="creator_category" class="visually-hidden">
                    {{ __('view_creator.directory.category_label') }}
                </label>
                {{-- Submitted by the search button next to it, so this needs no JS of its own --}}
                <select id="creator_category" name="category_id" class="form-select">
                    <option value="">{{ __('view_creator.directory.category_any') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                                @if($selectedCategory?->id === $category->id) selected @endif>
                            {{ $category->getTranslatedName() }}
                        </option>
                    @endforeach
                </select>
                @include('common.forms.form-error', ['key' => 'category_id'])
            </div>
        @endif
    </form>

    @if($creators->isEmpty())
        <p class="text-body-secondary">
            @if($selectedCategory !== null)
                {{ __('view_creator.directory.empty_for_category', ['category' => $selectedCategory->getTranslatedName()]) }}
            @elseif($search !== null)
                {{ __('view_creator.directory.empty_for_search', ['search' => $search]) }}
            @else
                {{ __('view_creator.directory.empty') }}
            @endif
        </p>
    @else
        <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-xl-4">
            @foreach($creators as $creator)
                <div class="col">
                    @include('creator.card', ['creator' => $creator])
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $creators->links() }}
        </div>
    @endif
@endsection
