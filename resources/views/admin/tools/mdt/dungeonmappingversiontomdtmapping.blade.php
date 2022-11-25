<?php
    /** @var $mappingVersionsSelect array */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.mdt.dungeonmappingversiontomdtmapping.title')])

@section('header-title', __('views/admin.tools.mdt.dungeonmappingversiontomdtmapping.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.mdt.dungeonmappingversiontomdtmapping.submit']) }}
    <div class="form-group">
        {!! Form::select('mapping_version_id', $mappingVersionsSelect, true, ['class' => 'selectpicker']) !!}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.mdt.dungeonmappingversiontomdtmapping.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
