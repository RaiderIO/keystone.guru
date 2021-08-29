@extends('layouts.sitepage', ['title' => __('views/profile.tags.title')])

@section('header-title')
    {{ __('views/profile.tags.header') }}
@endsection

@section('content')
    @include('common.general.messages')

    <p>
        {!!
            sprintf(
                __('views/profile.tags.description'),
                 '<a href="' . route('profile.routes') . '">' .
                 __('views/profile.tags.link_your_personal_route_overview') .
                 ' </a>'
             )
         !!}
    </p>

    @include('common.tag.manager', ['category' => \App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL])
@endsection