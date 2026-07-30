<?php

use App\Models\Affix;

/**
 * @var Affix $affix
 */
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$affix ?? null],
    'showAds' => false,
    'title' => isset($affix) ? __('view_admin.affix.edit.title_edit') : __('view_admin.affix.edit.title_new'),
    ])

@section('header-title')
    {{ isset($affix) ? __('view_admin.affix.edit.header_edit') : __('view_admin.affix.edit.header_new') }}
@endsection

@section('content')
    @isset($affix)
        {{ html()->modelForm($affix, 'PATCH', route('admin.affix.update', $affix))->open() }}
    @else
        {{ html()->form('POST', route('admin.affix.savenew'))->open() }}
    @endisset

    @isset($affix)
        <div class="mb-3">
            {{ html()->label(__('view_admin.affix.edit.id'), 'id') }}
            {{ html()->number('id')->class('form-control')->attribute('disabled', 'disabled') }}
        </div>

        <div class="mb-3">
            {{ __('view_admin.affix.edit.current_image') }}: <img src="{{ $affix->image_url }}"
                                                                    style="width: 32px; height: 32px;"/>
        </div>
    @endisset

    <div class="mb-3{{ $errors->has('key') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affix.edit.key'), 'key') }}
        {{ html()->text('key')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'key'])
    </div>

    <div class="mb-3{{ $errors->has('affix_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affix.edit.affix_id'), 'affix_id') }}
        {{ html()->number('affix_id')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'affix_id'])
    </div>

    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affix.edit.name'), 'name') }}
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="mb-3{{ $errors->has('description') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affix.edit.description'), 'description') }}
        {{ html()->textarea('description')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'description'])
    </div>

    {{ html()->input('submit')->value(__('view_admin.affix.edit.submit'))->class('btn btn-info') }}

    {{ html()->closeModelForm() }}
@endsection
