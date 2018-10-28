@extends('layouts.app', ['wide' => true])

@section('header-title')
    {{ __('Route listing') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('content')
@include('common.dungeonroute.table')
@endsection()