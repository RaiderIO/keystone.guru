@extends('layouts.app')

@section('header-title', __('View dungeons'))

@section('scripts')
<script type="text/javascript">
    $(function () {
        $('#admin_dungeon_table').DataTable({
            columns: [
                {data: 'id'},
                {data: 'name'},
                {data: 'key'}
            ]
        });
    });
</script>
@endsection

@section('content')
<a href="{{ route('admin.dungeon.new') }}" class="btn btn-success text-white pull-right" role="button">{{ __('Create dungeon') }}</a>

<table id="admin_dungeon_table" class="tablesorter">
    <thead>
    <tr>
        <th>{{ __('Id') }}</th>
        <th>{{ __('Name') }}</th>
        <th>{{ __('Key') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($dungeons->all() as $dungeon)
    <tr>
        <td>{{ $dungeon->id }}</td>
        <td>{{ $dungeon->name }}</td>
        <td>{{ $dungeon->key }}</td>
    </tr>
    @endforeach
    </tbody>

</table>
@endsection()