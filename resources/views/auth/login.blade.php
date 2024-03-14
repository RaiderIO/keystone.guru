@extends('layouts.sitepage', ['title' => __('view_auth.login.title'), 'showAds' => false])

@section('content')
    <div class="pt-4">
        @include('common.forms.login')
    </div>
@endsection
