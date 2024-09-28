<?php

use App\Models\MDTImport;
use Illuminate\Support\Collection;

/**
 * @var Collection<MDTImport> $mdtImports
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.list.title')])

@section('header-title')
    {{ __('view_admin.tools.mdt.list.header') }}
@endsection

@section('scripts')
    @parent

@endsection

@section('content')
    <!--suppress HtmlDeprecatedAttribute -->
    {{ $mdtImports->links() }}

    <table id="admin_user_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('view_admin.tools.mdt.list.table_header.id') }}</th>
            <th width="20%">{{ __('view_admin.tools.mdt.list.table_header.error') }}</th>
            <th width="50%">{{ __('view_admin.tools.mdt.list.table_header.import_string') }}</th>
            <th width="10%">{{ __('view_admin.tools.mdt.list.table_header.date') }}</th>
            <th width="10%">{{ __('view_admin.tools.mdt.list.table_header.actions') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($mdtImports as $mdtImport)
            <tr>
                <td>
                    {{ $mdtImport->id }}
                </td>
                <td>{{ $mdtImport->error }}</td>
                <td>{!! substr($mdtImport->import_string, 0, 75) !!}</td>
                <td>{{ $mdtImport->created_at->toDateTimeString() }}</td>
                <td>
                    <button type="button" class="btn btn-info"
                            data-toggle="tooltip" title="{{ __('view_admin.tools.mdt.list.copy_mdt_string') }}"
                            data-mdt-string="{!! $mdtImport->import_string !!}"
                            onclick="copyToClipboard($(this).data('mdt-string'))">
                        <i class="fas fa-copy"></i>
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $mdtImports->links() }}
@endsection
