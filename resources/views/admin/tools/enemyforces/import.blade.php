@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.enemyforces.title')])

@section('header-title', __('view_admin.tools.enemyforces.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.enemyforces.import.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.enemyforces.paste_mennos_export_json'), 'import_string') }}
        {{ html()->textarea('import_string', '')->class('form-control')->data('simplebar', '') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.enemyforces.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
