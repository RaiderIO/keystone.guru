@extends('layouts.sitepage', ['title' => __('views/dungeonroute.limitreached.title')])
@section('header-title', __('views/dungeonroute.limitreached.header'))

@section('content')
    <div class="mt-2 text-center">
        {{ sprintf(
            __('views/dungeonroute.limitreached.limit_reached_description'),
            config('keystoneguru.registered_user_dungeonroute_limit'))
         }}
        <br>
        <a href="https://www.patreon.com/keystoneguru">
            {!!  sprintf(__('views/dungeonroute.limitreached.become_a_patreon'), '<i class="fab fa-patreon"></i>') !!}
        </a>
    </div>
@endsection

