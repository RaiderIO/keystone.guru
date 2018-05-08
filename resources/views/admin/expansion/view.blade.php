@extends('layouts.app')

@section('header-title', __('View expansions'))

@section('head')
    <link rel="stylesheet" type="text/css"
          href="{{ url('//cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}"/>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ url('//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript">
        $(function () {
            $('#admin_expansion_table').DataTable({
                columns: [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'icon'},
                    {data: 'color'}
                ]
            });
        });
    </script>
@endsection

@section('content')
    <a href="{{ route('admin.expansion.new') }}" class="btn btn-success text-white pull-right"
       role="button">{{ __('Create expansion') }}</a>

    <table id="admin_expansion_table" class="tablesorter">
        <thead>
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Icon') }}</th>
            <th>{{ __('Color') }}</th>
            <th>{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($expansions->all() as $expansion)
            <tr>
                <td>{{ $expansion->id }}</td>
                <td>{{ $expansion->name }}</td>
                <td>{{ $expansion->icon }}</td>
                <td>{{ $expansion->color }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.expansion.edit', ['id' => $expansion->id]) }}">
                        <i class="fa fa-pencil"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()