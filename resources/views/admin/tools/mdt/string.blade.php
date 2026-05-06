<?php
$asDungeonroute ??= false;
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.string.title')])

@section('header-title', __('view_admin.tools.mdt.string.header'))

@section('content')
    {{ html()->form('POST', route($asDungeonroute ? 'admin.tools.mdt.string.viewasdungeonroute.submit' : 'admin.tools.mdt.string.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.mdt.string.paste_your_mdt_export_string'), 'import_string') }}
        {{ html()->textarea('import_string', '')->class('form-control')->data('simplebar', '') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.mdt.string.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
