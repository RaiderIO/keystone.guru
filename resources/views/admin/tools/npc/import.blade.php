@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.npc.import.title')])

@section('header-title', __('view_admin.tools.npc.import.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.npc.import.submit'))->open() }}
    <div class="mb-3">
        {{ html()->label(__('view_admin.tools.npc.import.paste_npc_import_string'), 'import_string') }}
        {{ html()->textarea('import_string', '')->class('form-control')->data('simplebar', '') }}
    </div>
    <div class="mb-3">
        {{ html()->input('submit')->value(__('view_admin.tools.npc.import.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
