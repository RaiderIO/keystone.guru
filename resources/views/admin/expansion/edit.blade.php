@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$expansion ?? null],
    'showAds' => false,
    'title' => ($expansion ?? null) ? __('view_admin.expansion.edit.title_edit') : __('view_admin.expansion.edit.title_new')
    ])
@section('header-title', ($expansion ?? null) ? __('view_admin.expansion.edit.header_edit') : __('view_admin.expansion.edit.header_new'))

@section('content')
    @isset($expansion)
        {{ html()->modelForm($expansion, 'PATCH', route('admin.expansion.update', $expansion))->open() }}
    @else
        {{ html()->form('POST', route('admin.expansion.savenew'))->open() }}
    @endisset

    <div class="mb-3{{ $errors->has('active') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.expansion.edit.active'), 'active') }}
        {{ html()->checkbox('active', isset($expansion) ? $expansion->active : 1, 1)->class('form-control left_checkbox') }}
        @include('common.forms.form-error', ['key' => 'active'])
    </div>

    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.expansion.edit.name'), 'name') }}
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.expansion.edit.shortname'), 'shortname') }}
        {{ html()->text('shortname')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'shortname'])
    </div>

    @isset($expansion)
        <div class="mb-3">
            {{ __('view_admin.expansion.edit.current_image') }}: <img src="{{ $expansion->getIconUrl() }}"
                                                                      style="width: 32px; height: 32px;"/>
        </div>
    @endisset

    <div class="mb-3{{ $errors->has('color') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.expansion.edit.color'), 'color') }}
        {{ html()->input('color', 'color')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'color'])
    </div>

    {{ html()->input('submit')->value(isset($expansion) ? __('view_admin.expansion.edit.edit') : __('view_admin.expansion.edit.submit'))->class('btn btn-info') }}

    {{ html()->closeModelForm() }}
@endsection
