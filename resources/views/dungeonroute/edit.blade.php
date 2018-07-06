@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

<?php
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get()->toArray();
$classes = \App\Models\CharacterClass::with('iconfile')->get()->toArray();
?>

@section('scripts')
    @parent

    <script>
        let _racesClasses = JSON.parse(atob('<?php echo base64_encode(json_encode($racesClasses)); ?>'));
        let _classes = JSON.parse(atob('<?php echo base64_encode(json_encode($classes)); ?>'));
        let _selectedDungeonId;
        let _currentStage = 1;
        let _maxStage = 2;

        let _stages = [
            {
                'id': 1,
                'saveCallback': function () {
                    _selectedDungeonId = $("#dungeon_selection").val();
                }
            }, {
                'id': 2,
                'saveCallback': function () {

                }
            }, {
                'id': 3,
                'initCallback': function () {
                    // Get the data of the selected dungeon
                    let dungeon = getDungeonDataById(_selectedDungeonId);
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

            $("#faction").bind('change', _factionChanged);
            $(".raceselect").bind('change', _raceChanged);
            _handleButtonVisibility();
            // Force population of the race boxes
            _factionChanged();
        });

        function _factionChanged() {
            console.log(">> _factionChanged");

            let newFaction = $("#faction").val();
            let $raceSelect = $("select.raceselect");

            // Remove all existing options
            $raceSelect.find('option').remove();

            // Re-fill the races
            $raceSelect.append(jQuery('<option>', {
                value: -1,
                text: "{{ __('Race...') }}"
            }));

            for (let i = 0; i < _racesClasses.length; i++) {
                let raceClass = _racesClasses[i];
                console.log(raceClass.faction, newFaction);
                if (raceClass.faction === newFaction) {
                    $raceSelect.append(jQuery('<option>', {
                        value: raceClass.id,
                        text: raceClass.name
                    }));
                }
            }

            $(".selectpicker").selectpicker('refresh');

            console.log("OK _factionChanged");
        }

        function _raceChanged() {

        }

        function _getStage(id) {
            for (let i = 0; i < _stages.length; i++) {
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
            let currentStage = _getStage(_currentStage);
            if (currentStage.hasOwnProperty('saveCallback')) {
                currentStage.saveCallback();
            }

            let nextStage = _getStage(stage);
            if (nextStage.hasOwnProperty('initCallback')) {
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

        <div id="stage-2" style="display: none;">
            <h2>
                {{ __('Group composition') }}
            </h2>
            <div class="form-group">
                {!! Form::label('faction', __('Select faction')) !!}
                {!! Form::select('faction', ['Horde' => 'Horde', 'Alliance' => 'Alliance'], 0, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                <div class="col-lg-2 col-lg-offset-1">
                    {!! Form::label('race_selection_1', __('Party member #1')) !!}
                    {!! Form::select('race_selection_1', [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect']) !!}

                    {!! Form::select('class_selection_1', [-1 => __('Class...')], 0, ['class' => 'form-control selectpicker']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::label('race_selection_2', __('Party member #2')) !!}
                    {!! Form::select('race_selection_2', [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect']) !!}

                    {!! Form::select('class_selection_2', [-1 => __('Class...')], 0, ['class' => 'form-control selectpicker']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::label('race_selection_3', __('Party member #3')) !!}
                    {!! Form::select('race_selection_3', [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect']) !!}

                    {!! Form::select('class_selection_3', [-1 => __('Class...')], 0, ['class' => 'form-control selectpicker']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::label('race_selection_4', __('Party member #4')) !!}
                    {!! Form::select('race_selection_4', [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect']) !!}

                    {!! Form::select('class_selection_4', [-1 => __('Class...')], 0, ['class' => 'form-control selectpicker']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::label('race_selection_5', __('Party member #5')) !!}
                    {!! Form::select('race_selection_5', [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect']) !!}

                    {!! Form::select('class_selection_5', [-1 => __('Class...')], 0, ['class' => 'form-control selectpicker']) !!}
                </div>
            </div>
        </div>
    </div>

    <div id="stage-3" style="display: none;">
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

