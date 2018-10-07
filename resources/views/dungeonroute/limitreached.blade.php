@extends('layouts.app')
@section('header-title', $headerTitle)

@section('content')
    {{ sprintf(
        __('You have reached the maximum amount of routes you may create (%s). Please consider becoming a Patron to continue making more routes, or delete some of your existing routes. Thank you for using the site!'),
        config('keystoneguru.registered_user_dungeonroute_limit'))
     }}
    <div class="mt-2 text-center">
        <a href="https://www.patreon.com/keystoneguru">Become a <i class="fab fa-patreon"></i> Patron!</a>
    </div>
@endsection

