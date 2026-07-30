@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.affix.list.title')])

@section('header-title')
    <div class="row">
        <div class="col-lg">
            <h4>{{ __('view_admin.affix.list.header') }}</h4>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.affix.new') }}" class="btn btn-success text-white float-end ms-auto"
               role="button">
                <i class="fas fa-plus"></i> {{ __('view_admin.affix.list.create_affix') }}
            </a>
        </div>
    </div>
@endsection

<?php

use App\Models\Affix;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Affix> $models
 */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_affix_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_affix_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('view_admin.affix.list.table_header_icon') }}</th>
            <th width="10%">{{ __('view_admin.affix.list.table_header_id') }}</th>
            <th width="10%">{{ __('view_admin.affix.list.table_header_affix_id') }}</th>
            <th width="20%">{{ __('view_admin.affix.list.table_header_key') }}</th>
            <th width="30%">{{ __('view_admin.affix.list.table_header_name') }}</th>
            <th width="10%">{{ __('view_admin.affix.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $affix)
            <tr>
                <td><img src="{{ $affix->image_url }}" style="width: 32px; height: 32px;"/></td>
                <td>{{ $affix->id }}</td>
                <td>{{ $affix->affix_id }}</td>
                <td>{{ $affix->key }}</td>
                <td>{{ __($affix->name) }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.affix.edit', ['affix' => $affix]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.affix.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
