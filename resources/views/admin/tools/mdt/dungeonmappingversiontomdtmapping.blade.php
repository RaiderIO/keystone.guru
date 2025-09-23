<?php
/**
 * @var array $mappingVersionsSelect
 */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.dungeonmappingversiontomdtmapping.title')])

@section('header-title', __('view_admin.tools.mdt.dungeonmappingversiontomdtmapping.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.mdt.dungeonmappingversiontomdtmapping.submit'))->open() }}
    <div class="form-group">
        {{ html()->select('mapping_version_id', $mappingVersionsSelect, true)->class('form-control selectpicker')->data('live-search', 'true') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.mdt.dungeonmappingversiontomdtmapping.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
