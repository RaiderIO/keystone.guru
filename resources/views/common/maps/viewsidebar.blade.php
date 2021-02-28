<?php
/** @var \App\Models\DungeonRoute $model */
$show = isset($show) ? $show : [];
$floorSelection = (!isset($floorSelect) || $floorSelect) && $model->dungeon->floors->count() !== 1;
?>

@include('common.general.inline', ['path' => 'common/maps/viewsidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#viewsidebar',
    'sidebarScrollSelector' => '#viewsidebar .sidebar-content',
    'sidebarToggleSelector' => '#viewsidebarToggle',
    'anchor' => 'left',
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $floorId,
    'dungeonroute' => $model
]])

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => $model->title,
    'subHeader' => $model->getSubHeaderHtml(),
    'anchor' => 'left',
    'id' => 'viewsidebar',
    'show' => $show,
])
    <!-- Details -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Details') }}</h5>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col-5 col-md-6 font-weight-bold">
                        {{ __('Timer') }}:
                    </div>
                    <div class="col-7 col-md-6" data-toggle="tooltip" title="{{ sprintf(__('+2: %s, +3: %s'),
                        gmdate('i:s', $model->dungeon->getTimerUpgradePlusTwoSeconds()),
                        gmdate('i:s', $model->dungeon->getTimerUpgradePlusThreeSeconds()))
                        }}">
                        {{ gmdate('i:s', $model->dungeon->timer_max_seconds) }}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Group setup') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div id="view_dungeonroute_group_setup" class="col">
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Affixes') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col">
                        {!! Form::select('affixes[]', $affixes, $selectedAffixes,
                            ['id' => 'affixes',
                            'class' => 'form-control affixselect selectpicker',
                            'multiple' => 'multiple',
                            'title' => __('Affixes'),
                            'readonly' => 'readonly',
                            'data-selected-text-format' => 'count > 1',
                            'data-count-selected-text' => __('{0} affixes selected')]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                @if($floorSelection)
                    <div class="row view_dungeonroute_details_row mt-3">
                        <div class="col font-weight-bold">
                            {{ __('Floor') }}:
                        </div>
                    </div>
                    <div class="row view_dungeonroute_details_row mt-1">
                        <div class="col floor_selection">
                            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                            {!! Form::select('map_floor_selection', [__('Select floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Interaction settings -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Interaction') }}</h5>
                <!-- Draw controls are injected here through drawcontrols.js -->
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {{ __('Rating') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {!! Form::select('rating', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
                                            $model->avg_rating, ['id' => 'rating', 'class' => 'form-control']) !!}
                    </div>
                </div>
                <!-- No mt-2 here because there's additional padding from the rating dropdown above -->
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {{ __('Your rating') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col">
                        @guest
                            {{ __('Login to rate this route') }}
                        @elseif( $model->isOwnedByUser() )
                            {{ __('You cannot rate your own route') }}
                        @else
                            {!! Form::select('your_rating', ['' => '', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
                                                $model->getRatingByCurrentUser(), ['id' => 'your_rating', 'class' => 'form-control', 'style' => 'width: 200px']) !!}
                        @endguest
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Favorite') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col">
                        @guest
                            {{ __('Login to favorite this route') }}
                        @else
                            {!! Form::checkbox('favorite', 1, $model->isFavoritedByCurrentUser(), ['id' => 'favorite', 'class' => 'form-control left_checkbox']) !!}
                        @endguest
                    </div>
                </div>
                @auth
                    @if($model->mayUserEdit(Auth::user()))
                        <div class="row view_dungeonroute_details_row mt-2">
                            <div class="col-12 font-weight-bold">
                                <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $model->public_key]) }}"
                                   target="_blank">
                                    <button class="btn btn-info w-100">
                                        <i class="fas fa-edit"></i>
                                        {{ __('Edit this route') }}
                                    </button>
                                </a>
                            </div>
                        </div>
                    @endif
                    @if($model->dungeon->active)
                        <div class="row view_dungeonroute_details_row mt-2">
                            <div class="col-12 font-weight-bold">
                                <a href="{{ route('dungeonroute.clone', ['dungeonroute' => $model->public_key]) }}"
                                   target="_blank">
                                    <button class="btn btn-info w-100">
                                        <i class="fa fa-clone"></i>
                                        {{ __('Clone this route') }}
                                    </button>
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="row view_dungeonroute_details_row mt-2">
                        <div class="col-12 font-weight-bold">
                            @isset($current_report)
                                <span class="text-warning">
                                    <i class="fa fa-exclamation-triangle"></i> {{ __('You have reported this route for moderation.') }}
                                </span>
                            @else
                                <a href="#" data-toggle="modal"
                                   data-target="#userreport_dungeonroute_modal">
                                    <button class="btn btn-warning w-100">
                                        <i class="fa fa-flag"></i>
                                        {{ __('Report for moderation') }}
                                    </button>
                                </a>
                            @endisset
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
@endcomponent