<?php

use App\Models\Season;

/**
 * @var Season      $currentSeason
 * @var Season|null $nextSeason
 * @var int         $routeKeyLevelFrom
 * @var int         $routeKeyLevelTo
 */

$dungeonSelectId = 'dungeon_id_select_temporary';
?>

@include('common.general.inline', ['path' => 'common/forms/createroutetemporary', 'options' => [
    'levelSelector' => '#temporary_dungeon_route_level',
    'dungeonSelector' => sprintf('#%s', $dungeonSelectId),
    'currentSeason' => $currentSeason,
    'nextSeason' => $nextSeason,
    'keyLevelMinDefault' => config('keystoneguru.keystone.levels.default_min'),
    'keyLevelMaxDefault' => config('keystoneguru.keystone.levels.default_max'),
    'levelFrom' => $routeKeyLevelFrom,
    'levelTo' => $routeKeyLevelTo,
]])

{{ Form::open(['route' => 'dungeonroute.temporary.savenew']) }}
<div class="container">
    @if( !isset($model) )
        @include('common.dungeon.select', ['id' => $dungeonSelectId, 'showAll' => false, 'showSeasons' => true])
    @endif

    <div class="form-group">
        <label for="dungeon_route_level">
            {{ __('view_common.forms.createroutetemporary.key_levels') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_common.forms.createroutetemporary.key_levels_title') }}"></i>
        </label>
        {!! Form::text('dungeon_route_level', sprintf('%d;%d', $routeKeyLevelFrom, $routeKeyLevelTo),
            ['id' => 'temporary_dungeon_route_level', 'class' => 'form-control', 'style' => 'display: none;']) !!}
    </div>

    <div class="form-group">
        <div class="text-info">
            @guest
                <i class="fas fa-info-circle"></i> {{ sprintf(
                    __('view_common.forms.createroutetemporary.unregistered_user_message'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )
                }}
            @else
                <i class="fas fa-info-circle"></i> {{
            sprintf(
                __('view_common.forms.createroutetemporary.registered_user_message'),
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )
                }}
            @endguest
        </div>
    </div>

    @include('common.dungeonroute.create.dungeondifficultyselect', ['id' => 'dungeon_difficulty_select_temporary', 'dungeonSelectId' => $dungeonSelectId])

    <div class="col-lg-12">
        <div class="form-group">
            {!! Form::submit(__('view_common.forms.createroutetemporary.create_route'), ['class' => 'btn btn-info col-md-auto']) !!}
        </div>
    </div>
</div>

{!! Form::close() !!}
