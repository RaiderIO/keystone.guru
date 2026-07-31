<?php

use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Season> $models
 */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.season.list.title')])

@section('header-title')
    <div class="row">
        <div class="col-lg">
            <h4>{{ __('view_admin.season.list.header') }}</h4>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.season.new') }}" class="btn btn-success text-white float-end ms-auto"
               role="button">
                <i class="fas fa-plus"></i> {{ __('view_admin.season.list.create_season') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_season_table').DataTable({
                'aaSorting': [],
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_season_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('view_admin.season.list.table_header_id') }}</th>
            <th width="10%">{{ __('view_admin.season.list.table_header_expansion') }}</th>
            <th width="30%">{{ __('view_admin.season.list.table_header_name') }}</th>
            <th width="15%">{{ __('view_admin.season.list.table_header_start') }}</th>
            <th width="10%">{{ __('view_admin.season.list.table_header_affix_group_count') }}</th>
            <th width="15%">{{ __('view_admin.season.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $season)
            <tr>
                <td>{{ $season->id }}</td>
                <td>{{ __($season->expansion->name) }}</td>
                <td>{{ __($season->name_long) }}</td>
                <td data-order="{{ $season->start->timestamp }}">{{ $season->start->toDateString() }}</td>
                <td>{{ $season->affix_group_count }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.season.edit', ['season' => $season]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.season.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
