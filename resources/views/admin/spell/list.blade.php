@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.spell.list.title')])

@section('header-title')
    {{ __('views/admin.spell.list.header') }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.spell.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('views/admin.spell.list.create_spell') }}
    </a>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_spell_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <table id="admin_spell_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('views/admin.spell.list.table_header_icon') }}</th>
            <th width="10%">{{ __('views/admin.spell.list.table_header_id') }}</th>
            <th width="70%">{{ __('views/admin.spell.list.table_header_name') }}</th>
            <th width="10%">{{ __('views/admin.spell.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $spell)
            <tr>
                <td><img src="{{ $spell->icon_url }}" width="48px"/></td>
                <td>{{ $spell->id }}</td>
                <td>{{ $spell->name }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.spell.edit', ['spell' => $spell->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('views/admin.spell.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
