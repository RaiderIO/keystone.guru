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
            <th width="10%">{{ __('Dungeon') }}</th>
            <th width="10%" class="hidden-xs">{{ __('Affixes') }}</th>
            <th width="15%" class="hidden-xs">{{ __('Setup') }}</th>
            <th width="10%" class="hidden-xs">{{ __('Author') }}</th>
            <th width="10%">{{ __('Rating') }}</th>
            <th width="5%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $route)
            <?php /** @var $route \App\Models\DungeonRoute */ ?>
            <tr>
                <td>{{ $route->title }}</td>
                <td>{{ $route->dungeon->name }}</td>
                <td class="hidden-xs">{{ sprintf(__('%s selected'), count($route->affixgroups)) }}</td>
                <td class="hidden-xs">
                    <img src="{{ Image::url($route->faction->iconfile->getUrl(), 32, 32) }}" class="select_icon faction_icon"/> |
                    @foreach($route->classes as $class)
                        <?php /** @var $class \App\Models\CharacterClass */ ?>
                        <img src="{{ Image::url($class->iconfile->getUrl(), 32, 32) }}" class="select_icon class_icon"/>
                    @endforeach
                </td>
                <td class="hidden-xs">{{ $route->author->name }}</td>
                <td>{{ $route->rating }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('dungeonroute.edit', ['id' => $route->id]) }}">
                        <i class="fas fa-pencil"></i>&nbsp;<span class="hidden-xs"> {{ __('Edit') }} </span>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection