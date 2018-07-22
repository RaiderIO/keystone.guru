@extends('layouts.app')

@section('header-title')
    {{ __('View expansions') }}
    <a href="{{ route('admin.expansion.new') }}" class="btn btn-success text-white pull-right" role="button">
        <i class="fa fa-plus"></i> {{ __('Create expansion') }}
    </a>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_expansion_table').DataTable({
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_expansion_table" class="tablesorter default_table">
        <thead>
        <tr>
            <th width="10%">{{ __('Icon') }}</th>
            <th width="10%">{{ __('Id') }}</th>
            <th width="50%">{{ __('Name') }}</th>
            <th width="20%">{{ __('Color') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
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