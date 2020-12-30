<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */

$show = isset($show) ? $show : [];
// May not be set in the case of a sandbox version
if (isset($model)) {
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
}

if (\Illuminate\Support\Facades\Auth::check()) {
    $tags = \App\Models\Tags\TagModel::where('user_id', \Illuminate\Support\Facades\Auth::id())->get()->map(function(\App\Models\Tags\TagModel $tagModel){
        return $tagModel->tag;
    });
} else {
    $tags = collect();
}
?>
@include('common.general.inline', ['path' => 'common/maps/editsidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#editsidebar',
    'sidebarScrollSelector' => '#editsidebar .sidebar-content',
    'sidebarToggleSelector' => '#editsidebarToggle',
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $floorId,
    'anchor' => 'left'
]])

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => __('Toolbox'),
    'anchor' => 'left',
    'id' => 'editsidebar'
])
    @isset($show['sharing'])
        @include('common.maps.share', ['model' => $model])
    @endisset

    <!-- Tags -->
    @if(isset($show['tags']) && $show['tags'])
        <div class="form-group">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Tags') }}</h5>
                    @include('common.maps.sidebartags', ['tagmodels' => $model->tagmodels, 'edit' => true])
                </div>
            </div>
        </div>
    @endisset

    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Visibility') }}</h5>
                <div class="row">
                    <div class="col">
                        <div class="leaflet-draw-section">
                            <div id="map_enemy_visuals" class="form-group">
                                <div class="font-weight-bold">{{ __('Enemy display type') }}:</div>
                                <div id="map_enemy_visuals_container">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="map_map_object_group_visibility_container">
                    <div class="row view_dungeonroute_details_row">
                        <div class="col font-weight-bold">
                            {{ __('Map elements') }}:
                        </div>
                    </div>
                    <div class="row view_dungeonroute_details_row">
                        <div class="col">
                            {!! Form::select('map_map_object_group_visibility', [], 0,
                                ['id' => 'map_map_object_group_visibility',
                                'class' => 'form-control selectpicker',
                                'multiple' => 'multiple',
                                'data-selected-text-format' => 'count > 1',
                                'data-count-selected-text' => __('{0} visible')]) !!}
                        </div>
                    </div>
                </div>

                @if($floorSelection)
                    <div id="map_floor_selection_container">
                        <div class="row view_dungeonroute_details_row mt-3">
                            <div class="col font-weight-bold">
                                {{ __('Floor') }}:
                            </div>
                        </div>
                        <div class="row view_dungeonroute_details_row">
                            <div class="col">
                                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                                {!! Form::select('map_floor_selection', [__('Select floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                            </div>
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="form-group route_actions">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Actions') }}</h5>

                @isset($show['virtual-tour'])
                    <div class="form-group">
                        <!-- Virtual tour -->
                        <div class="row">
                            <div class="col">
                                <button id="start_virtual_tour" class="btn btn-info col">
                                    <i class="fas fa-info-circle"></i> {{ __('Start virtual tour') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset

                @isset($show['draw-settings'])
                    <div class="form-group">
                        <!-- Route settings -->
                        <div class="row">
                            <div id="map_draw_settings_btn_container" class="col">
                                <button class="btn btn-info col" data-toggle="modal" data-target="#draw_settings_modal">
                                    <i class='fas fa-palette'></i> {{ __('Draw settings') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset

                @isset($show['route-settings'])
                    <div class="form-group">
                        <!-- Route settings -->
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-info col" data-toggle="modal"
                                        data-target="#route_settings_modal">
                                    <i class='fas fa-cog'></i> {{ __('Route settings') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset


                @isset($show['route-publish'])
                <!-- Published state -->
                    <div class="form-group mb-0">
                        <div class="row">
                            <div id="map_route_publish_container" class="col"
                                 data-toggle="tooltip"
                                 title="{{ __('Kill enough enemy forces and kill all unskippable enemies to publish your route') }}"
                                 style="display: block">
                                <button id="map_route_publish"
                                        class="btn btn-success col-md {{ $model->published === 1 ? 'd-none' : '' }}"
                                        disabled>
                                    <i class="fa fa-plane-departure"></i> {{ __('Publish route') }}
                                </button>
                                <button id="map_route_unpublish"
                                        class="btn btn-warning col-md {{ $model->published === 0 ? 'd-none' : '' }}">
                                    <i class="fa fa-plane-arrival"></i> {{ __('Unpublish route') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset

                @isset($show['sandbox'])
                    @if (Auth::guest())
                        <div id="map_login_and_continue" class="form-group">
                            <button class="btn btn-primary mt-1 w-100" data-toggle="modal" data-target="#login_modal">
                                <i class="fas fa-sign-in-alt"></i> {{__('Login and continue')}}
                            </button>
                        </div>
                        <div id="map_register_and_continue" class="form-group">
                            <button class="btn btn-primary mt-1 w-100" data-toggle="modal"
                                    data-target="#register_modal">
                                <i class="fas fa-user-plus"></i> {{ __('Register and continue') }}
                            </button>
                        </div>
                    @else
                        <div id="map_save_and_continue" class="form-group">
                            <a href="{{ route('dungeonroute.claim', ['dungeonroute' => $model->public_key]) }}"
                               class="btn btn-primary mt-1 w-100" role="button">
                                <i class="fas fa-save"></i> {{ __('Save and continue') }}
                            </a>
                        </div>
                    @endif
                @endisset
            </div>
        </div>
    </div>

    <!-- Mouseover enemy information -->
    <div id="enemy_info_container" class="form-group" style="display: none">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Enemy info') }}</h5>
                <div id="enemy_info_key_value_container">

                </div>
                <div class="row mt-2">
                    <div class="col">
                        <a href="#" data-toggle="modal"
                           data-target="#userreport_enemy_modal">
                            <button class="btn btn-warning w-100">
                                <i class="fa fa-bug"></i>
                                {{ __('Report an issue') }}
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcomponent

@isset($show['draw-settings'])
    @component('common.general.modal', ['id' => 'draw_settings_modal'])
        <div class="draw_settings_tools">
            <?php // Weight ?>
            <div class="form-group">
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {{ __('Default line weight') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col line_weight_selection">
                        <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                        {!! Form::select('edit_route_freedraw_options_weight', [1, 2, 3, 4, 5],
                            isset($_COOKIE['polyline_default_weight']) ? $_COOKIE['polyline_default_weight'] : 0,
                            ['id' => 'edit_route_freedraw_options_weight', 'class' => 'form-control selectpicker']) !!}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Pull gradient') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-3">
                    <div id="edit_route_freedraw_options_gradient" class="col-10">

                    </div>
                    <div class="col-2">
                        <button id="edit_route_freedraw_options_gradient_apply_to_pulls" class="btn btn-success"
                                data-toggle="tooltip" title="{{ __('Apply to current pulls') }}">
                            {{ __('Apply') }}
                        </button>
                        <button id="edit_route_freedraw_options_gradient_apply_to_pulls_saving"
                                class="btn btn-success disabled"
                                style="display: none">
                            <i class='fas fa-circle-notch fa-spin'></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row no-gutters view_dungeonroute_details_row">
                    <div class="col-2 pr-2">
                        {!! Form::checkbox('pull_gradient_apply_always', 1,
                            isset($model) ? $model->pull_gradient_apply_always : false,
                            ['id' => 'pull_gradient_apply_always', 'class' => 'form-control left_checkbox'])
                            !!}

                    </div>
                    <div class="col-10">
                        <label for="pull_gradient_apply_always">
                            {{ __('Always apply when I change pulls') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group mb-0">
                <button id="save_draw_settings" class="offset-lg-4 col-lg-4 btn btn-success">
                    <i class="fas fa-save"></i> {{ __('Save') }}
                </button>
                <button id="save_draw_settings_saving" class="offset-lg-5 col-lg-2 btn btn-success disabled"
                        style="display: none;">
                    <i class="fas fa-circle-notch fa-spin"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    @endcomponent
@endisset

@isset($show['route-settings'])
    @component('common.general.modal', ['id' => 'route_settings_modal', 'size' => 'lg'])

        <div class='col-lg-12'>
            <h3>
                {{ __('General') }}
            </h3>
            <div class='form-group'>
                <label for="dungeon_route_title">
                    {{ __('Title') }} <span class="form-required">*</span>
                    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                                        __('Choose a title that will uniquely identify the route for you over other similar routes you may create.')
                                         }}"></i>
                </label>
                {!! Form::text('dungeon_route_title', $model->title, ['id' => 'dungeon_route_title', 'class' => 'form-control']) !!}

{{--                <label for="teeming">--}}
{{--                    {{ __('Teeming') }}--}}
{{--                    <i class="fas fa-info-circle" data-toggle="tooltip" title="{{--}}
{{--                                            __('Check to change the dungeon to resemble Teeming week. Warning: any selected Teeming enemies will be removed from your existing pulls (when disabling Teeming).')--}}
{{--                                             }}"></i>--}}
{{--                </label>--}}
{{--                {!! Form::checkbox('teeming', 1, $model->teeming, ['id' => 'teeming', 'class' => 'form-control left_checkbox']) !!}--}}
            </div>
            @include('common.dungeonroute.attributes', ['dungeonroute' => $model])

            <h3 class='mt-1'>
                {{ __('Affixes') }} <span class="form-required">*</span>
            </h3>

            <div class='container mt-1'>
                @include('common.group.affixes', ['dungeonroute' => $model, 'teemingselector' => '#teeming', 'modal' => '#route_settings_modal'])
            </div>

            <h3>
                {{ __('Group composition') }}
            </h3>

            @php($factions = $model->dungeon->isSiegeOfBoralus() ? \App\Models\Faction::where('name', '<>', 'Unspecified')->get() : null)
            @include('common.group.composition', ['dungeonroute' => $model, 'factions' => $factions, 'modal' => '#route_settings_modal'])

            @if(Auth::user()->hasPaidTier(\App\Models\PaidTier::UNLISTED_ROUTES) )
                <h3>
                    {{ __('Sharing') }}
                </h3>
                <div class='form-group'>
                    {!! Form::label('unlisted', __('Private (when checked, only people with the link can view your route)')) !!}
                    {!! Form::checkbox('unlisted', 1, $model->unlisted, ['class' => 'form-control left_checkbox']) !!}
                </div>
            @endif

            @if(Auth::user()->hasRole('admin'))
                <h3>
                    {{ __('Admin') }}
                </h3>
                <div class='form-group'>
                    {!! Form::label('demo', __('Demo route')) !!}
                    {!! Form::checkbox('demo', 1, $model->demo, ['class' => 'form-control left_checkbox']) !!}
                </div>
            @endif

            <div class='form-group'>
                <div id='save_route_settings' class='offset-lg-5 col-lg-2 btn btn-success'>
                    <i class='fas fa-save'></i> {{ __('Save settings') }}
                </div>
                <div id='save_route_settings_saving' class='offset-lg-5 col-lg-2 btn btn-success disabled'
                     style='display: none;'>
                    <i class='fas fa-circle-notch fa-spin'></i>
                </div>
            </div>
        </div>
    @endcomponent
@endisset