@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.expansion.list.title')])

@section('header-title')
    <div class="row">
        <div class="col-lg">
            <h4>{{ __('view_admin.expansion.list.header') }}</h4>
        </div>
        <div class="ml-auto">
            <a href="{{ route('admin.expansion.new') }}" class="btn btn-success text-white pull-right ml-auto"
               role="button">
                <i class="fas fa-plus"></i> {{ __('view_admin.expansion.list.create_expansion') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_expansion_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_expansion_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="50px">{{ __('view_admin.expansion.list.table_header_active') }}</th>
            <th width="10%">{{ __('view_admin.expansion.list.table_header_icon') }}</th>
            <th width="10%">{{ __('view_admin.expansion.list.table_header_id') }}</th>
            <th width="50%">{{ __('view_admin.expansion.list.table_header_name') }}</th>
            <th width="20%">{{ __('view_admin.expansion.list.table_header_color') }}</th>
            <th width="10%">{{ __('view_admin.expansion.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($expansions->all() as $expansion)
            <tr>
                @if($expansion->active)
                    <td data-order="{{ $expansion->id }}">
                        <i class="fas fa-check-circle text-success"></i>
                    </td>
                @else
                    <td data-order="{{ $expansion->id + 1000 }}">
                        <i class="fas fa-times-circle text-danger"></i>
                    </td>
                @endif
                <td><img src="{{ ksgAssetImage(sprintf('expansions/%s.png', $expansion->shortname)) }}"
                         style="width: 50px;"/></td>
                <td>{{ $expansion->id }}</td>
                <td>{{ __($expansion->name) }}</td>
                <td>{{ $expansion->color }}</td>
                <td>
                    <a class="btn btn-primary"
                       href="{{ route('admin.expansion.edit', ['expansion' => $expansion]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.expansion.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()
