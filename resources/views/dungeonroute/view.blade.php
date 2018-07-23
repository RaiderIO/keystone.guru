@extends('layouts.app', ['wide' => true])

@section('header-title')
    {{ __('View routes') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('scripts')
<script type="text/javascript">
    $(function () {
        $('#routes_table').DataTable({});
    });
</script>
@endsection

@section('content')
<table id="routes_table" class="tablesorter default_table">
    <thead>
    <tr>
        <th width="40%">{{ __('Title') }}</th>
        <th width="15%">{{ __('Dungeon') }}</th>
        <th width="10%">{{ __('Affixes') }}</th>
        <th width="10%">{{ __('Setup') }}</th>
        <th width="10%">{{ __('Author') }}</th>
        <th width="10%">{{ __('Rating') }}</th>
        <th width="5%">{{ __('Actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($models->all() as $route)
    <tr>
        <td>{{ $route->title }}</td>
        <td>{{ $route->dungeon->name }}</td>
        <td>{{ $route->affixes }}</td>
        <td>{{ $route->setup }}</td>
        <td>{{ $route->author->name }}</td>
        <td>{{ $route->rating }}</td>
        <td>
            <a class="btn btn-primary" href="{{ route('dungeonroute.edit', ['id' => $route->id]) }}">
                <i class="fa fa-pencil"></i>&nbsp;{{ __('Edit') }}
            </a>
        </td>
    </tr>
    @endforeach
    </tbody>

</table>
@endsection