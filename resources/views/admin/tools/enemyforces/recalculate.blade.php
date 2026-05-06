@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.enemyforces.recalculate.title')])

@section('header-title', __('view_admin.tools.enemyforces.recalculate.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.enemyforces.recalculate.submit'))->open() }}
    <div class="form-group">
        @include('common.dungeon.select', ['activeOnly' => false])
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.enemyforces.recalculate.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
