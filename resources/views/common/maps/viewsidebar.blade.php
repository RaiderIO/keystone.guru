<?php
/** @var \App\Models\DungeonRoute $model */
$floorSelection = (!isset($floorSelect) || $floorSelect) && $model->dungeon->floors->count() !== 1;

// Only add the 'clone of' when the user cloned it from someone else as a form of credit
$cloneTitle = isset($model->clone_of) && \App\Models\DungeonRoute::where('public_key', $model->clone_of)->where('author_id', $model->author_id)->count() === 0 ?
    sprintf('%s %s',
        __('Clone of'),
        ' <a href="' . route('dungeonroute.view', ['dungeonroute' => $model->clone_of]) . '">' . $model->clone_of . '</a>')
    : '';
?>

@section('scripts')
    @parent

    <script>
        let _dungeonRoute = {!! $model !!};

        $(function () {
            $("#view_dungeonroute_group_setup").html(
                handlebarsGroupSetupParse(_dungeonRoute.setup)
            );
            $('#rating').barrating({
                theme: 'bars-1to10',
                readonly: true,
                initialRating: {{ $model->avg_rating }}
            });
            $('#your_rating').barrating({
                theme: 'bars-1to10',
                deselectable: true,
                allowEmpty: true,
                onSelect: function (value, text, event) {
                    rate(value);
                }
            });
            $('#favorite').bind('change', function (el) {
                favorite($('#favorite').is(':checked'));
            });

            refreshTooltips();
        });

        /**
         * Rates the current dungeon route or unset it.
         * @param value int
         */
        function rate(value) {
            let isDelete = value === '';
            $.ajax({
                type: isDelete ? 'DELETE' : 'POST',
                url: '/ajax/dungeonroute/' + _dungeonRoute.public_key + '/rate',
                dataType: 'json',
                data: {
                    rating: value
                },
                success: function (json) {
                    // Update the new average rating
                    $('#rating').barrating('set', Math.round(json.new_avg_rating));
                }
            });
        }

        /**
         * Favorites the current dungeon route, or not.
         * @param value bool
         */
        function favorite(value) {
            $.ajax({
                type: !value ? 'DELETE' : 'POST',
                url: '/ajax/dungeonroute/' + _dungeonRoute.public_key + '/favorite',
                dataType: 'json',
                success: function (json) {

                }
            });
        }
    </script>
@endsection

@section('sidebar-content')
    @include('common.userreport.dungeonroute', ['model' => $model])

    <!-- Enemy forces -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Enemy forces') }}</h5>
                <!-- Draw controls are injected here through drawcontrols.js -->
                <div id="edit_route_enemy_forces_container">

                </div>
            </div>
        </div>
    </div>


    <!-- Details -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Details') }}</h5>

                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col-5 col-md-6 font-weight-bold">
                        {{ __('Author') }}:
                    </div>
                    <div class="col-7 col-md-6">
                        {{ $model->author->name }}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col-5 col-md-6 font-weight-bold">
                        {{ __('Dungeon') }}:
                    </div>
                    <div class="col-7 col-md-6">
                        {{ $model->dungeon->name }}
                    </div>
                </div>
                {{--<div class="row view_dungeonroute_details_row mt-2">--}}
                {{--<div class="col-6 font-weight-bold">--}}
                {{--{{ __('Difficulty') }}:--}}
                {{--</div>--}}
                {{--<div class="col-6">--}}
                {{--{{ $model->difficulty }}--}}
                {{--</div>--}}
                {{--</div>--}}
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col-5 col-md-6 font-weight-bold">
                        {{ __('Teeming') }}:
                    </div>
                    <div class="col-7 col-md-6">
                        {{ $model->teeming ? __('Yes') : __('No') }}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Group setup') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div id="view_dungeonroute_group_setup" class="col">
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Affixes') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div id="affixgroup_select_container" class="col">
                        {!! Form::select('affixes[]', $affixes, $selectedAffixes,
                            ['id' => 'affixes',
                            'class' => 'form-control affixselect selectpicker',
                            'multiple' => 'multiple',
                            'title' => __('Expand to view'),
                            'readonly' => 'readonly',
                            'data-selected-text-format' => 'count > 1',
                            'data-count-selected-text' => __('{0} affixes selected')]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interaction settings -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Interaction') }}</h5>
                <!-- Draw controls are injected here through drawcontrols.js -->
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col font-weight-bold">
                        {{ __('Rating') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
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
                <div class="row view_dungeonroute_details_row mt-2">
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
                <!-- No mt-2 here because there's additional padding from the rating dropdown above -->
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {{ __('Favorite') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row mt-2">
                    <div class="col">
                        @guest
                            {{ __('Login to favorite this route') }}
                        @else
                            {!! Form::checkbox('favorite', 1, $model->isFavoritedByCurrentUser(), ['id' => 'favorite', 'class' => 'form-control left_checkbox']) !!}
                        @endguest
                    </div>
                </div>
                @auth
                    @if($model->dungeon->active)
                        <div class="row view_dungeonroute_details_row mt-2">
                            <div class="col-12 font-weight-bold">
                                <i class="fa fa-clone"></i>
                                <a href="{{ route('dungeonroute.clone', ['dungeonroute' => $model->public_key]) }}"
                                   target="_blank">
                                    {{ __('Clone this route') }}
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="row view_dungeonroute_details_row mt-2">
                        <div class="col-12 font-weight-bold">
                            @isset($current_report)
                                <span class="text-warning">
                                    <i class="fa fa-exclamation-triangle"></i> {{ __('You have reported this dungeonroute for moderation.') }}
                                </span>
                            @else
                                <i class="fa fa-flag"></i>
                                <a id="featherlight_trigger" href="#"
                                   data-featherlight="#userreport_dungeonroute">
                                    {{ __('Report for moderation') }}
                                </a>
                            @endisset
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Visibility') }}</h5>
                <div class="row">
                    <div id="map_enemy_visuals_container" class="col">
                    </div>
                </div>

                @if($floorSelection)
                    <div class="row view_dungeonroute_details_row">
                        <div class="col font-weight-bold">
                            {{ __('Floor') }}:
                        </div>
                    </div>
                    <div class="row view_dungeonroute_details_row mt-2">
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
@endsection

@include('common.maps.sidebar', ['header' => $model->title, 'subHeader' => $cloneTitle])