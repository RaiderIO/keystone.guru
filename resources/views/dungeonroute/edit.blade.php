@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('scripts')
    @parent

    <script>
        var _selectedDungeonId;
        var _currentStage = 1;
        var _maxStage = 2;

        var _stages = [
            {
                'id': 1,
                'saveCallback': function () {
                    _selectedDungeonId = $("#dungeon_selection").val();
                }
            }, {
                'id': 2,
                'initCallback': function () {
                    // Get the data of the selected dungeon
                    var dungeon = getDungeonDataById(_selectedDungeonId);
                    // First floor, always
                    setCurrentMapName(dungeon.key, 1);
                    updateFloorSelection();
                    // Refresh the map to reflect changes
                    refreshLeafletMap();
                },
                'saveCallback': function () {
                }
            }
        ];

        $(function () {
            $("#previous").bind('click', _previousStage);
            $("#next").bind('click', _nextStage);
            _handleButtonVisibility();
        });

        function _getStage(id) {
            for (var i = 0; i < _stages.length; i++) {
                if (_stages[i].id === id) {
                    return _stages[i];
                }
            }
            return null;
        }

        function _previousStage() {
            if (_currentStage > 1) {
                _setStage(_currentStage - 1);
            }
            _handleButtonVisibility();
        }

        function _nextStage() {
            if (_currentStage < _maxStage) {
                _setStage(_currentStage + 1);
            }
            _handleButtonVisibility();
        }

        function _handleButtonVisibility() {
            if (_currentStage === 1) {
                $("#previous").hide();
            } else {
                $("#previous").show();
            }

            if (_currentStage === _maxStage) {
                $("#next").hide();
            } else {
                $("#next").show();
            }
        }

        function _setStage(stage) {
            $("#stage-" + _currentStage).hide();
            $("#stage-" + stage).show();
            var currentStage = _getStage(_currentStage);
            if( currentStage.hasOwnProperty('saveCallback') ){
                currentStage.saveCallback();
            }

            var nextStage = _getStage(stage);
            if( nextStage.hasOwnProperty('initCallback') ){
                nextStage.initCallback();
            }

            _currentStage = stage;
        }
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew', 'files' => true]) }}
    @endisset
    <div id="setup_container" class="container">
        <div class="col-lg-12">
            {!! Form::button('<i class="fa fa-backward"></i> ' . __('Previous'), ['id' => 'previous', 'class' => 'btn btn-info col-lg-1', 'style' => 'display: none;']) !!}
            {!! Form::button('<i class="fa fa-forward"></i> ' . __('Next'), ['id' => 'next', 'class' => 'btn btn-info col-lg-offset-11 col-lg-1']) !!}
        </div>

        <div id="stage-1">
            <div class="form-group">
                {!! Form::label('dungeon_selection', __('Select dungeon')) !!}
                {!! Form::select('dungeon_selection', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>

    <div id="stage-2" style="display: none;">
        <div id="map_container">
            @include('common.maps.map', [
                'admin' => false,
                'dungeons' => \App\Models\Dungeon::all(),
                'dungeonSelect' => false,
                'manualInit' => true
            ])

            {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
        </div>
    </div>

    {!! Form::close() !!}
@endsection

