@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

<?php
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get()->toArray();
$classes = \App\Models\CharacterClass::with('iconfile')->get()->toArray();
?>

@section('head')
    <style>
        .class_icon {
            width: 24px;
            border-radius: 12px;
            background-color: #1d3131;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        let _racesClasses = JSON.parse(atob('<?php echo base64_encode(json_encode($racesClasses)); ?>'));
        let _classDetails = JSON.parse(atob('<?php echo base64_encode(json_encode($classes)); ?>'));
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

            $(".selectpicker").selectpicker({
                showIcon: true
            });

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
            console.log(">> _raceChanged");

            let $raceSelect = $(this);
            console.log($raceSelect[0]);
            let $classSelect = $("#class_selection_" + $raceSelect.data('id'));
            let raceId = parseInt($raceSelect.val());
            console.log($classSelect[0]);

            $classSelect.find('option').remove();

            // Re-fill the races
            $classSelect.append(jQuery('<option>', {
                value: -1,
                text: "{{ __('Class...') }}",
                'data-thumbnail': 'images/classes/druid.png'
            }));

            // Find the raceclass for the class we've selected
            let raceClass = null;
            for (let i = 0; i < _racesClasses.length; i++) {
                if (_racesClasses[i].id === raceId) {
                    raceClass = _racesClasses[i];
                    break;
                }
            }

            console.assert(raceClass !== null, "RaceClass it not set (selected invalid class?)");

            // Match the raceClass to the classDetails
            for (let i = 0; i < raceClass.classes.length; i++) {
                let rClass = raceClass.classes[i];
                // Find the details
                for (let j = 0; j < _classDetails.length; j++) {
                    let classDetail = _classDetails[j];
                    // If found
                    if (classDetail.id === rClass.id) {
                        // Display it
                        $classSelect.append(jQuery('<option>', {
                            value: classDetail.id, //zzz
                            text: classDetail.name,
                            'data-content': $("#template_dropdown_icon").html()
                                .replace("{image}", '../../images/' + classDetail.iconfile.path)
                                .replace("{text}", classDetail.name)
                        }));
                        break;
                    }
                }
            }

            $('.selectpicker').selectpicker('refresh'); ///zzz
            $('.selectpicker').selectpicker('render'); ///zzz

            console.log("OK _raceChanged");
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
                <?php for($i = 1; $i <= 5; $i++){ ?>
                <div class="col-lg-2{{ $i === 1 ? ' col-lg-offset-1' : '' }}">
                    {!! Form::label('race_selection_' . $i, __('Party member #' . $i)) !!}
                    {!! Form::select('race_selection_' . $i, [-1 => __('Race...')], 0, ['class' => 'form-control selectpicker raceselect', 'data-id' => $i]) !!}

                    {!! Form::select('class_selection_' . $i, [-1 => __('Class...')], 0,
                    ['id' => 'class_selection_' . $i, 'class' => 'form-control selectpicker', 'data-id' => $i]) !!}
                </div>
                <?php } ?>
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


    <div id="template_dropdown_icon" style="display: none;">
        <span>
            <img src="{image}" class="class_icon"/> {text}
        </span>
    </div>

    {!! Form::close() !!}
@endsection

