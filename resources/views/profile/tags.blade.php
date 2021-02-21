@extends('layouts.app', ['title' => __('My tags')])

@section('header-title')
    {{ __('My tags') }}
@endsection

@section('content')
    @include('common.general.messages')

    <p>
        {!!  sprintf(
                __('The tagging feature allows you to organize your routes the way you see fit. You can add tags to routes by viewing the Actions for each route in %s.
                You can manage tags for your own routes here. Nobody else will be able to view your tags - for routes attached to a team
                you can manage a separate set of tags for just that team by visiting the Tags section when viewing your team.'),
                 '<a href="' . route('profile.routes') . '">' . __('your personal route overview') . ' </a>')!!}
    </p>

    @include('common.tag.manager', ['category' => \App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL])
@endsection