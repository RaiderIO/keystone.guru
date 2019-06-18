@extends('layouts.app', [
    'showLegalModal' => false,
    'title' => $title
])

@section('title', $title)

@section('content')
    <div class="row">
        <div class="h1 text-center col" style="font-size: 10rem">
            {{ $code }}
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="h3 text-center col">
            {{ $message }}
        </div>
    </div>
@endsection