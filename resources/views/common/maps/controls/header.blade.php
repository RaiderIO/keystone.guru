<?php

use App\Logic\MapContext\MapContext;
use App\Logic\MapContext\MapContextDungeonExplore;
use App\Logic\MapContext\MapContextLiveSession;
use App\Logic\MapContext\MapContextMappingVersionEdit;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Models\Team;
use App\Models\User;

/**
 * @var string              $theme
 * @var bool                $isUserAdmin
 * @var MapContext          $mapContext
 * @var Dungeon             $dungeon
 * @var Floor               $floor
 * @var DungeonRoute|null   $dungeonroute
 * @var LiveSession|null    $livesession
 * @var MappingVersion|null $mappingVersion
 * @var bool                $edit
 */

$echo               ??= false;
$mayUserEdit        = $dungeonroute?->mayUserEdit(Auth::user()) ?? false;
$showShare          = !empty($show['share']) && in_array(true, $show['share'], true);
$showCreateRouteBtn = isset($dungeonroute) && $dungeonroute->isSandbox();

$seasonalAffix = $dungeonroute?->getSeasonalAffix()?->key;
?>
<nav id="map_header"
     class="map_fade_out navbar navbar-expand-xl {{ $theme === User::THEME_LUX ? 'navbar-light' : 'navbar-dark' }}">
    <div class="container bg-header">
        <a class="navbar-brand" href="/">
            <img src="{{ ksgAssetImage('logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                 height="44px;" width="200px;">
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('view_common.maps.controls.header.toggle_navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-xl-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                @isset($dungeonroute)
                    @component('common.maps.controls.buttons.headerbutton')
                        @if( $mapContext instanceof MapContextLiveSession )
                                <?php $stopped = $livesession->expires_at !== null; ?>
                            @if(!$stopped)
                                <button id="stop_live_session" class="btn btn-danger btn-sm"
                                        data-toggle="modal" data-target="#stop_live_session_modal">
                                    <i class="fas fa-stop"></i> {{ __('view_common.maps.controls.header.stop') }}
                                </button>
                            @endif
                            <div id="stopped_live_session_container" class="row no-gutters"
                                 style="display: {{ $stopped ? 'inherit' : 'none' }}">
                                <div class="row">
                                    <div class="col">
                                    <span id="stopped_live_session_countdown">
                                        {{ $stopped ? sprintf(__('view_common.maps.controls.header.live_session_expires_in'), $livesession->getExpiresInHoursSeconds()) : '' }}
                                    </span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        @if($mayUserEdit)
                                            <a href="{{ route('dungeonroute.edit', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                                               class="btn-sm btn-success w-100">
                                                <i class="fas fa-edit"></i> {{ __('view_common.maps.controls.header.edit_route') }}
                                            </a>
                                        @else
                                            <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                                               class="btn-sm btn-success w-100">
                                                <i class="fas fa-eye"></i> {{ __('view_common.maps.controls.header.view_route') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <button class="btn btn-success btn-sm w-100"
                                    data-toggle="modal" data-target="#start_live_session_modal">
                                <i class="fas fa-play"></i> {{ __('view_common.maps.controls.header.start') }}
                            </button>
                        @endif
                    @endcomponent
                    <li class="nav-item nav-item-divider">

                    </li>
                @endisset
                @component('common.maps.controls.buttons.headerbutton')
                    <div class="row no-gutters">
                        <div class="col my-1">
                            <div class="row no-gutters">
                                @auth
                                    @isset($dungeonroute)
                                            <?php $isFavoritedByCurrentUser = $dungeonroute->isFavoritedByCurrentUser(); ?>
                                        <div class="col-auto">
                                            <h5 class="mb-0 mr-2 pt-1">
                                                <i id="route_favorited" class="fas fa-star favorite_star favorited"
                                                   style="display: {{ $isFavoritedByCurrentUser ? 'inherit' : 'none' }}"></i>
                                                <i id="route_not_favorited" class="far fa-star favorite_star"
                                                   style="display: {{ $isFavoritedByCurrentUser ? 'none' : 'inherit' }}"></i>
                                                {{ html()->hidden('favorite', $isFavoritedByCurrentUser ? '1' : '0')->id('favorite') }}
                                            </h5>
                                        </div>
                                    @endisset
                                @endauth
                                @if($seasonalAffix !== null)
                                    @php($seasonalAffixKey = strtolower(Str::slug($seasonalAffix, '_')))
                                    <div class="col-auto">
                                        <img class="select_icon mr-1"
                                             src="{{ ksgAssetImage(sprintf('affixes/%s.jpg', $seasonalAffixKey)) }}"
                                             alt="{{ __('view_common.maps.controls.header.seasonal_affix') }}"
                                             data-toggle="tooltip"
                                             title="{{ __(sprintf('affixes.%s.name', $seasonalAffixKey)) }}"
                                        />
                                    </div>
                                @endif
                                <div class="col">
                                    <h5 id="route_title" class="mb-0 mr-2">
                                        @isset($dungeonroute)
                                            {{ $dungeonroute->title }}
                                        @elseif($mapContext instanceof MapContextDungeonExplore)
                                            {{ __('view_common.maps.controls.header.explore_header_title', ['dungeon' => __($dungeon->name)]) }}
                                        @else
                                            <a href="{{ route('admin.floor.edit', ['dungeon' => $floor->dungeon, 'floor' => $floor]) }}">
                                                {{ sprintf(__('view_common.maps.controls.header.admin_header_title'), __($dungeon->name), $mappingVersion->version) }}
                                            </a>
                                        @endisset
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!($mapContext instanceof MapContextDungeonExplore))
                        @if(isset($dungeonroute) && $dungeonroute->team instanceof Team)
                            <div class="row no-gutters">
                                <div class="col">
                                <span class="text-primary">
                                    @if($dungeonroute->team->isUserMember(Auth::user()))
                                        <a href="{{ route('team.edit', ['team' => $dungeonroute->team]) }}">
                                            <i class="fas fa-users"></i> {{ $dungeonroute->team->name }}
                                        </a>
                                    @else
                                        <i class="fas fa-users"></i> {{ $dungeonroute->team->name }}
                                    @endif
                                </span>
                                </div>
                            </div>
                        @elseif(isset($dungeonroute) && !$dungeonroute->mappingVersion->isLatestForDungeon())
                            <div class="row no-gutters">
                                <div class="col">
                                    <span data-toggle="tooltip"
                                          title="{{ __('view_common.maps.map.new_mapping_version_header_description') }}">
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                        {{ __('view_common.maps.map.new_mapping_version_header_title') }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endif
                @endcomponent
            </ul>
            @if($echo)
                <ul class="navbar-nav">
                    @component('common.maps.controls.buttons.headerbutton')
                        @include('common.layout.nav.connectedusers')
                    @endcomponent
                </ul>
            @endif
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider">

                </li>
                @auth
                    @if( $showCreateRouteBtn )
                        @component('common.maps.controls.buttons.headerbutton')
                            <a href="{{ route('dungeonroute.claim', [
                                    'dungeon' => $dungeonroute->dungeon,
                                    'title' => $dungeonroute->getTitleSlug(),
                                    'dungeonroute' => $dungeonroute]
                                ) }}">
                                <button class="btn btn-success btn-sm w-100">
                                    <i class="fas fa-save"></i> {{ __('view_common.maps.controls.header.save_to_profile') }}
                                </button>
                            </a>
                        @endcomponent
                    @endif
                @endauth

                @isset($dungeonroute)
                    @if($isUserAdmin)
                        @component('common.maps.controls.buttons.headerbutton')
                            <button id="edit_route_admin_settings_button" class="btn btn-info btn-sm w-100"
                                    data-toggle="modal" data-target="#edit_route_admin_settings_modal">
                                <i class="fas fa-toolbox"></i> {{ __('view_common.maps.controls.header.edit_route_admin_settings') }}
                            </button>
                        @endcomponent
                    @endif

                    @component('common.maps.controls.buttons.headerbutton')
                        <button id="simulate_route_button" class="btn btn-info btn-sm w-100"
                                data-toggle="modal" data-target="#simulate_modal">
                            <i class="fas fa-atom"></i> {{ __('view_common.maps.controls.header.simulate_route') }}
                        </button>
                    @endcomponent

                    @if(!$dungeonroute->isSandbox() && $edit)
                        @component('common.maps.controls.buttons.headerbutton')
                            <button id="edit_route_settings_button" class="btn btn-info btn-sm w-100"
                                    data-toggle="modal" data-target="#edit_route_settings_modal">
                                <i class="fas fa-cog"></i> {{ __('view_common.maps.controls.header.edit_route_settings') }}
                            </button>
                        @endcomponent
                    @endif
                @endisset
                @if( $mapContext instanceof MapContextDungeonExplore && $isUserAdmin )
                    @component('common.maps.controls.buttons.headerbutton')
                        <a href="{{ route('admin.floor.edit.mapping', [
                                    'dungeon' => $dungeon,
                                    'floor' => $dungeon->floors()->first(),
                                    'mapping_version' => $dungeon->getCurrentMappingVersion()
                                ]) }}">
                            <button class="btn btn-success btn-sm w-100">
                                <i class="fas fa-cog"></i> {{ __('view_common.maps.controls.header.edit_mapping_version') }}
                            </button>
                        </a>
                    @endcomponent
                @endif
                @if( $mapContext instanceof MapContextMappingVersionEdit )
                    @component('common.maps.controls.buttons.headerbutton')
                        <button id="edit_mapping_version_button" class="btn btn-info btn-sm w-100"
                                data-toggle="modal" data-target="#edit_mapping_version_modal">
                            <i class="fas fa-cog"></i> {{ __('view_common.maps.controls.header.edit_mapping_version') }}
                        </button>
                    @endcomponent
                @endif


                @if($showShare)
                    @component('common.maps.controls.buttons.headerbutton')
                        <button class="btn btn-info btn-sm w-100"
                                data-toggle="modal" data-target="#share_modal">
                            <i class="fas fa-share"></i> {{ __('view_common.maps.controls.header.share') }}
                        </button>
                    @endcomponent
                @endif
                <li class="nav-item nav-item-divider">

                </li>
                @include('common.layout.nav.user')
                {{--                @include('common.layout.nav.themeswitch')--}}
            </ul>
        </div>
    </div>
</nav>

@isset($dungeonroute)

    @if($showShare)
        @component('common.general.modal', ['id' => 'share_modal'])
            @include('common.modal.share', ['show' => $show['share'], 'dungeonroute' => $dungeonroute, 'modalId' => 'share_modal'])
        @endcomponent
    @endif

    @auth
        @if($isUserAdmin)
            @component('common.general.modal', ['id' => 'edit_route_admin_settings_modal', 'size' => 'xl'])
                @include('common.modal.routeadminsettings', ['dungeonRoute' => $dungeonroute])
            @endcomponent
        @endif
    @endauth

    @if($showCreateRouteBtn)
        @component('common.general.modal', ['id' => 'edit_route_settings_modal', 'size' => 'xl'])
            @include('common.modal.routesettings', ['dungeonroute' => $dungeonroute])
        @endcomponent
    @endif

    @component('common.general.modal', ['id' => 'simulate_modal', 'size' => 'xl'])
        @include('common.modal.simulate', ['dungeonroute' => $dungeonroute])
    @endcomponent

    @component('common.general.modal', ['id' => 'start_live_session_modal'])
        <h3 class="card-title">{{ __('view_common.maps.controls.header.start_live_session') }}</h3>

        <p>
            {{ __('view_common.maps.controls.header.start_live_session_paragraph_1') }}
            <br><br>
            {{ __('view_common.maps.controls.header.start_live_session_paragraph_2') }}
            <br><br>
            {{ __('view_common.maps.controls.header.start_live_session_paragraph_3') }}
            <br><br>
            {{ __('view_common.maps.controls.header.start_live_session_paragraph_4') }}
        </p>

        <div class="row">
            <div class="col">
                <a href="{{ route('dungeonroute.livesession.create', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                   class="btn btn-success w-100">
                    <i class="fas fa-play"></i> {{ __('view_common.maps.controls.header.create_live_session') }}
                </a>
            </div>
        </div>
    @endcomponent

    @component('common.general.modal', ['id' => 'stop_live_session_modal'])
        <h3 class="card-title">{{ __('view_common.maps.controls.header.live_session_concluded') }}</h3>

            <?php // You cannot rate your own routes ?>
        @if($dungeonroute->author_id !== Auth::id())
                <?php $currentRating = $dungeonroute->getRatingByCurrentUser() ?>
            <div class="form-group">
                <h5>
                    <label for="rating_select">
                        {{ __('view_common.maps.controls.header.rate_this_route') }}
                    </label>
                </h5>
                <select id="rating_select" name="rating_select">
                    @for($i = 1; $i <= 10; $i++)
                        <option
                            value="{{ $i }}" {{ $currentRating !== null && (int) $currentRating === $i ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            @if($currentRating === null)
                <div class="form-group">
                    <p>
                        {{ __('view_common.maps.controls.header.rate_this_route_explanation') }}
                    </p>
                </div>
            @endif
        @else
            <div class="form-group">
                <p>
                    {{ __('view_common.maps.controls.header.you_cannot_rate_your_own_route') }}
                </p>
            </div>
        @endif

        <div class="row form-group">
            <div class="col">
                <button data-dismiss="modal" class="btn btn-outline-info w-100">
                    <i class="fas fa-chart-line"></i> {{ __('view_common.maps.controls.header.review_live_session') }}
                </button>
            </div>
            <div class="col">
                @if($mayUserEdit)
                    <a href="{{ route('dungeonroute.edit', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                       class="btn btn-success w-100">
                        <i class="fas fa-edit"></i> {{ __('view_common.maps.controls.header.edit_route') }}
                    </a>
                @else
                    <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                       class="btn btn-success w-100">
                        <i class="fas fa-eye"></i> {{ __('view_common.maps.controls.header.view_route') }}
                    </a>
                @endif
            </div>
        </div>
    @endcomponent
@elseif($mapContext instanceof MapContextMappingVersionEdit)
    @component('common.general.modal', ['id' => 'edit_mapping_version_modal', 'size' => 'xl'])
        @include('common.modal.mappingversion', ['mappingVersion' => $mappingVersion])
    @endcomponent
@endisset
