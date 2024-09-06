<?php

use Illuminate\Support\Collection;

/**
 * @var Collection<string> $features
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.features.list.title')])

@section('header-title')
    {{ __('view_admin.tools.features.list.header') }}
@endsection

@section('scripts')
    @parent

@endsection

@section('content')
    <!--suppress HtmlDeprecatedAttribute -->
    <table id="admin_user_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('view_admin.tools.features.list.table_header.enabled') }}</th>
            <th width="70%">{{ __('view_admin.tools.features.list.table_header.feature') }}</th>
            <th width="20%">{{ __('view_admin.tools.features.list.table_header.actions') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            @foreach($features as $feature)
                <td>
                    @if(\Laravel\Pennant\Feature::active($feature))
                        <i class="fas fa-check-circle text-success"></i>
                    @else
                        <i class="fas fa-times-circle text-danger"></i>
                    @endif
                </td>
                <td>{{ $feature }}</td>
                <td>
                    <div class="row">
                        <div class="col-auto">
                            <form class="form-horizontal" method="POST"
                                  action="{{ route('admin.tools.features.toggle') }}">
                                {{ csrf_field() }}
                                <input type="hidden" name="feature" value="{{ $feature }}"/>
                                <input class="btn btn-info" type="submit"
                                       value="{{ __('view_admin.tools.features.list.actions.toggle') }}">
                            </form>
                        </div>
                        <div class="col-auto">
                            <form class="form-horizontal" method="POST"
                                  action="{{ route('admin.tools.features.forget') }}">
                                {{ csrf_field() }}
                                <input type="hidden" name="feature" value="{{ $feature }}"/>
                                <input class="btn btn-info" type="submit"
                                       value="{{ __('view_admin.tools.features.list.actions.forget') }}">
                            </form>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                </td>
            @endforeach
        </tr>
        </tbody>
    </table>
@endsection
