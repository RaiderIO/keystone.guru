<?php

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @var LengthAwarePaginator<int, User> $creators
 * @var string|null                     $search
 */
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
    </form>

    @if($creators->isEmpty())
        <p class="text-body-secondary">
            {{ $search !== null
                ? __('view_creator.directory.empty_for_search', ['search' => $search])
                : __('view_creator.directory.empty') }}
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
