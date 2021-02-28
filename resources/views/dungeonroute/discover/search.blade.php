@extends('layouts.sitepage', ['wide' => true, 'title' => __('Routes')])

@section('header-title')
    {{ __('Routes') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('content')
    <h2>
        {{ __('Categories') }}
    </h2>
    this week's affix, next week's affix, simple, complex,
    <h2>
        {{ __('Popular routes (most views of last 7 days?)') }}
    </h2>
@endsection