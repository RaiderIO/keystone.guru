<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\Season;
use App\Models\User;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @var LengthAwarePaginator<int, User>                        $creators
 * @var string|null                                            $search
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 * @var DungeonRouteCollectionCategory|null                    $selectedCategory
 * @var Dungeon|null                                           $selectedDungeon
 * @var CreatorDirectorySort                                   $sort
 * @var Season|null                                            $statsSeason
 */

$categories       ??= collect();
$selectedCategory ??= null;
$selectedDungeon  ??= null;
?>
@extends('layouts.sitepage', [
    'wide'  => true,
    'title' => __('view_creator.directory.title'),
])

@section('header-title')
    {{ __('view_creator.directory.header') }}
@endsection

@section('content')
    <p class="creator_directory_intro text-body-secondary">
        {{ __('view_creator.directory.description', ['min' => config('keystoneguru.creators.min_published_routes')]) }}
    </p>

    <form method="GET" action="{{ route('creators.index') }}" class="row g-2 mb-4" role="search">
        @if($selectedDungeon !== null)
            <input type="hidden" name="dungeon" value="{{ $selectedDungeon->slug }}"/>
        @endif

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
            <div class="col-12 col-md-6 col-lg-4">
                <div class="input-group">
                    <label for="creator_category" class="input-group-text">
                        {{ __('view_creator.directory.category_label') }}
                    </label>
                    {{-- Submitted by the search button, so this needs no JS of its own --}}
                    <select id="creator_category" name="category_id" class="form-select">
                        <option value="">{{ __('view_creator.directory.category_any') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    @if($selectedCategory?->id === $category->id) selected @endif>
                                {{ __('view_creator.directory.category_option', ['category' => $category->getTranslatedName()]) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @include('common.forms.form-error', ['key' => 'category_id'])
            </div>
        @endif

        {{-- Without a season there is nothing for "active this season" to order by, and a dungeon filter ranks by
             popularity in that dungeon whatever the sort says --}}
        @if($statsSeason !== null && $selectedDungeon === null)
            <div class="col-12 col-md-6 col-lg-3">
                <div class="input-group">
                    <label for="creator_sort" class="input-group-text">
                        {{ __('view_creator.directory.sort_label') }}
                    </label>
                    <select id="creator_sort" name="sort" class="form-select">
                        @foreach(CreatorDirectorySort::cases() as $sortOption)
                            <option value="{{ $sortOption->value }}"
                                    @if($sort === $sortOption) selected @endif>
                                {{ $sortOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @include('common.forms.form-error', ['key' => 'sort'])
            </div>
        @endif
        @include('common.forms.form-error', ['key' => 'dungeon'])
    </form>

    @if($selectedDungeon !== null)
        <p class="creator_directory_dungeon_filter mb-4">
            {{ __('view_creator.directory.filtered_to_dungeon', ['dungeon' => __($selectedDungeon->name)]) }}
            <a href="{{ route('creators.index', array_filter([
                    'search'      => $search,
                    'category_id' => $selectedCategory?->id,
                ], static fn($value): bool => $value !== null)) }}"
               class="ms-2 text-nowrap">
                <i class="fas fa-times" aria-hidden="true"></i>
                {{ __('view_creator.directory.clear_dungeon_filter') }}
            </a>
        </p>
    @endif

    @if($creators->isEmpty())
        <p class="text-body-secondary">
            @if($search !== null)
                {{ __('view_creator.directory.empty_for_search', ['search' => $search]) }}
            @elseif($selectedDungeon !== null)
                {{ __('view_creator.directory.empty_for_dungeon', ['dungeon' => __($selectedDungeon->name)]) }}
            @elseif($selectedCategory !== null)
                {{ __('view_creator.directory.empty_for_category', ['category' => $selectedCategory->getTranslatedName()]) }}
            @else
                {{ __('view_creator.directory.empty') }}
            @endif
        </p>
    @else
        <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-xl-4">
            @foreach($creators as $creator)
                <div class="col">
                    @include('creator.card', ['creator' => $creator, 'statsSeason' => $statsSeason])
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $creators->links() }}
        </div>
    @endif
@endsection
