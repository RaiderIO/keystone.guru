@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.credits.title')])

@section('header-title', __('views/misc.credits.header'))

@section('content')

    <?php
    \App\Logic\Utils\Stopwatch::dumpAll();
    ?>

@endsection
