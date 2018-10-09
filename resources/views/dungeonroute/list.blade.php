@extends('layouts.app', ['wide' => true])

@section('header-title')
    {{ __('View routes') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('content')
<div class="container-fluid alert alert-info text-center mt-4">
    <i class="fa fa-info-circle"></i> {{ __('All routes now require publishing before they show up in this list. You will see less routes until people start publishing more of their routes.') }}
</div>
@include('common.dungeonroute.table')
@endsection()