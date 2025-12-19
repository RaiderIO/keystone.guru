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

    @include('common.tag.manager', ['context' => Auth::user(), 'category' => TagCategory::DUNGEON_ROUTE_PERSONAL])
@endsection
