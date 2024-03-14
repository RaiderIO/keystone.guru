@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.credits.title')])

@section('header-title', __('view_misc.credits.header'))

@section('content')

    <?php
    \App\Logic\Utils\Stopwatch::dumpAll();
    ?>

@endsection
