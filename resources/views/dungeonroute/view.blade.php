@extends('layouts.app', ['wide' => true])
@section('header-title', $model->title)

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
        });

        function rate(value) {
            $.ajax({
                type: 'POST',
                url: '/ajax/dungeonroute/' + _dungeonRoute.public_key + '/rate',
                dataType: 'json',
                data: {
                    rating: value
                },
                success: function (json) {
                    // We don't _really_ care if this doesn't go through or not
                }
            });
        }
    </script>
@endsection
@section('content')
    <h2 class="text-center">
        {{ __('Details') }}
    </h2>
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Author') }}:
        </div>
        <div class="col-6">
            {{ $model->author->name }}
        </div>
    </div>
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Dungeon') }}:
        </div>
        <div class="col-6">
            {{ $model->dungeon->name }}
        </div>
    </div>
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Affixes') }}:
        </div>
        <div class="col-6">
            <div id="affixgroup_select_container" style="width: 200px">
                {!! Form::select('affixes[]', $model->affixes->pluck('text', 'id'), $model->affixes->pluck('id'),
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
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Group setup') }}:
        </div>
        <div id="view_dungeonroute_group_setup" class="col-6">
        </div>
    </div>
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Rating') }}:
        </div>
        <div class="col-6">
            {!! Form::select('rating', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10], $model->avg_rating, ['id' => 'rating', 'class' => 'form-control', 'style' => 'width: 200px']) !!}
        </div>
    </div>
    <div class="row view_dungeonroute_details_row">
        <div class="col-md-2 ml-auto font-weight-bold">
            {{ __('Your rating') }}:
        </div>
        <div class="col-6">
            @if(Auth::user() === null )
                {{ __('Login to rate this route') }}
            @else
                {!! Form::select('your_rating', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10], null, ['id' => 'your_rating', 'class' => 'form-control', 'style' => 'width: 200px']) !!}
            @endif
        </div>
    </div>
    <div class="col-lg-12 mt-5">
        <div id="map_container">
            @include('common.maps.map', [
                'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id),
                'model' => $model,
                'edit' => false
            ])
        </div>
    </div>
@endsection

