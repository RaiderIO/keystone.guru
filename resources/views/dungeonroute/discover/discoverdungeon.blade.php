@extends('layouts.sitepage', ['title' => sprintf('%s routes', $dungeon->name)])

@section('header-title')
    {{ sprintf('%s routes', $dungeon->name) }}
@endsection
<?php
/**
 * @var $dungeon \App\Models\Dungeon
 */
?>

@section('content')
    <h2>
        {{ __('By current affix') }}
    </h2>
    <h3>
        {{ __('Popular routes (most views of last 7 days?)') }}
    </h3>
    <div>
        list
    </div>

    <h3>
        {{ __('By popular route creators (by which users had the most views last 7 days?)') }}
    </h3>
    <div>
        list
    </div>




    <h2>
        {{ __('By any affix') }}
    </h2>

@endsection