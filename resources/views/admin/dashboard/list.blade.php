@extends('layouts.sitepage', ['showAds' => false, 'title' => __('Admin dashboard')])

@section('header-title', __('Admin dashboard'))

@section('content')
    USERS
    Users registered + timeline:
    Users registered today:
    Users using OAuth
    Average legal agree accept time:
    Active users last week:
    Active users last 30 days:
    Active users last 180 days:

    ROUTES
    Routes created + timeline:
    Routes created today:
    Total views on routes:





    TEAMS
    Amount of teams + timeline:
    Amount of teams created last week:
    Amount of users attached to teams:
    Amount of routes attached to teams:
    Average users in a team:
    Average routes in a team:






@endsection