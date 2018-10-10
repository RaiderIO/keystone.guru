@extends('layouts.app', ['wide' => true])
@section('header-title', $model->title)
<?php
/** @var $model \App\Models\DungeonRoute */
$affixes = $model->affixes->pluck('text', 'id');
$selectedAffixes = $model->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes = [-1 => 'Any'];
    $selectedAffixes = -1;
}
?>

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $model->affixes])
    @include('common.handlebars.groupsetup')

    <script>
        var _dungeonRoute = {!! $model !!};

        $(function () {
            $("#view_dungeonroute_group_setup").html(
                handlebarsGroupSetupParse(_dungeonRoute)
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
@section('content')
    @include('common.userreport.dungeonroute', ['model' => $model])

    <h2 class="text-center">
        {{ __('Details') }}
    </h2>
    <div class="container">
        <div class="row">
            <!-- First column -->
            <div class="col-md-6">
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Author') }}:
                    </div>
                    <div class="col-6">
                        {{ $model->author->name }}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Dungeon') }}:
                    </div>
                    <div class="col-6">
                        {{ $model->dungeon->name }}
                    </div>
                </div>
                {{--<div class="row view_dungeonroute_details_row">--}}
                {{--<div class="col-6 font-weight-bold">--}}
                {{--{{ __('Difficulty') }}:--}}
                {{--</div>--}}
                {{--<div class="col-6">--}}
                {{--{{ $model->difficulty }}--}}
                {{--</div>--}}
                {{--</div>--}}
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Teeming') }}:
                    </div>
                    <div class="col-6">
                        {{ $model->teeming ? __('Yes') : __('No') }}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Group setup') }}:
                    </div>
                    <div id="view_dungeonroute_group_setup" class="col-6">
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Affixes') }}:
                    </div>
                    <div class="col-6">
                        <div id="affixgroup_select_container" style="width: 200px">
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

            <!-- Second column -->
            <div class="col-md-6">
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Rating') }}:
                    </div>
                    <div class="col-6">
                        {!! Form::select('rating', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
                                            $model->avg_rating, ['id' => 'rating', 'class' => 'form-control', 'style' => 'width: 200px']) !!}
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Your rating') }}:
                    </div>
                    <div class="col-6">
                        @guest
                            {{ __('Login to rate this route') }}
                        @elseif( $model->isOwnedByCurrentUser() )
                            {{ __('You cannot vote on your own routes')  }}
                        @else
                            @if($model->author_id === Auth::user()->id)
                                {{ __('You cannot rate your own route') }}
                            @else
                                {!! Form::select('your_rating', ['' => '', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
                                                    $model->getRatingByCurrentUser(), ['id' => 'your_rating', 'class' => 'form-control', 'style' => 'width: 200px']) !!}
                            @endif
                        @endguest
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col-6 font-weight-bold">
                        {{ __('Favorite') }}:
                    </div>
                    <div class="col-6">
                        @guest
                            {{ __('Login to favorite this route') }}
                        @else
                            {!! Form::checkbox('favorite', 1, $model->isFavoritedByCurrentUser(), ['id' => 'favorite', 'class' => 'form-control left_checkbox']) !!}
                        @endguest
                    </div>
                </div>
                @auth
                    <div class="row view_dungeonroute_details_row">
                        <div class="col-12 font-weight-bold">
                            @isset($current_report)
                                <span class="text-warning">
                                    <i class="fa fa-exclamation-triangle"></i> {{ __('You have reported this dungeonroute for moderation.') }}
                                </span>
                            @else
                                <i class="fa fa-flag"></i>
                                <a id="featherlight_trigger" href="#" data-featherlight="#userreport_dungeonroute">
                                    {{ __('Report for moderation') }}
                                </a>
                            @endisset
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
    <div class="col-lg-12 mt-5">
        <div id="map_container">
            @include('common.maps.map', [
                'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id),
                'dungeonroute' => $model,
                'edit' => false
            ])
        </div>
    </div>
@endsection

