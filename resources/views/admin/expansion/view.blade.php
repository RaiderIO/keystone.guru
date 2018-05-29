@extends('layouts.app')

@section('header-title', __('View expansions'))

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_expansion_table').DataTable({
                columns: [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'icon'},
                    {data: 'color'},
                    {data: 'actions'}
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
            <th>{{ __('Icon') }}</th>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Color') }}</th>
            <th>{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $expansion)
            <tr>
                <td><img src="{{ Image::url($expansion->icon->getUrl(), 32, 32) }}"/></td>
                <td>{{ $expansion->id }}</td>
                <td>{{ $expansion->name }}</td>
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