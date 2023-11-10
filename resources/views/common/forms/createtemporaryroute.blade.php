<?php
/** @var int $routeKeyLevelFrom */
/** @var int $routeKeyLevelTo */
$dungeonSelectId      = 'dungeon_id_select_temporary';
?>

@include('common.general.inline', ['path' => 'common/forms/createtemporaryroute', 'options' => [
    'levelSelector' => '#temporary_dungeon_route_level',
    'levelMin' => config('keystoneguru.keystone.levels.min'),
    'levelMax' => config('keystoneguru.keystone.levels.max'),
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
            {{ __('views/common.forms.createtemporaryroute.key_levels') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('views/common.forms.createtemporaryroute.key_levels_title') }}"></i>
        </label>
        {!! Form::text('dungeon_route_level', sprintf('%d;%d', $routeKeyLevelFrom, $routeKeyLevelTo),
            ['id' => 'temporary_dungeon_route_level', 'class' => 'form-control', 'style' => 'display: none;']) !!}
    </div>

    <div class="form-group">
        <div class="text-info">
            @guest
                <i class="fas fa-info-circle"></i> {{ sprintf(
                    __('views/common.forms.createtemporaryroute.unregistered_user_message'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )
                }}
            @else
                <i class="fas fa-info-circle"></i> {{
            sprintf(
                __('views/common.forms.createtemporaryroute.registered_user_message'),
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )
                }}
            @endguest
        </div>
    </div>

    @include('common.dungeonroute.create.dungeonspeedrunrequirednpcsdifficulty', ['id' => 'dungeon_speedrun_required_npc_mode_temporary', 'dungeonSelectId' => $dungeonSelectId])

    <div class="col-lg-12">
        <div class="form-group">
            {!! Form::submit(__('views/common.forms.createtemporaryroute.create_route'), ['class' => 'btn btn-info col-md-auto']) !!}
        </div>
    </div>
</div>

{!! Form::close() !!}
