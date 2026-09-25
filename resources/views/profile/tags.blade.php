<?php
    use App\Models\Tags\TagCategory;
?>
@extends('layouts.sitepage', ['title' => __('view_profile.tags.title')])

@section('header-title')
    {{ __('view_profile.tags.header') }}
@endsection

@section('content')
    <p>
        {!!
            sprintf(
                __('view_profile.tags.description'),
                 '<a href="' . route('profile.routes') . '">' .
                 __('view_profile.tags.link_your_personal_route_overview') .
                 ' </a>'
             )
         !!}
    </p>

    @feature(\App\Features\CreatorProfiles::class)
        <p>
            {!!
                sprintf(
                    __('view_profile.tags.collections'),
                    '<a href="' . route('collections.index') . '">' .
                    __('view_profile.tags.link_collections') .
                    '</a>'
                )
            !!}
        </p>
    @endfeature

    @include('common.tag.manager', ['context' => Auth::user(), 'category' => TagCategory::DUNGEON_ROUTE_PERSONAL])
@endsection
