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

        let _stages = [
            {
                'id': 1,
                'saveCallback': function () {
                    _selectedDungeonId = $("#dungeon").val();
                }
            }, {
                'id': 2,
                'saveCallback': function () {

                }
            },
            // {
            //     'id': 3,
            //     'initCallback': function () {
            //         // Get the data of the selected dungeon
            //         let dungeon = getDungeonDataById(_selectedDungeonId);
            //         // First floor, always
            //         setCurrentMapName(dungeon.key, 1);
            //         updateFloorSelection();
            //         // Refresh the map to reflect changes
            //         refreshLeafletMap();
            //     },
            //     'saveCallback': function () {
            //
            //     }
            // }
        ];

        $(function () {
            $("#previous").bind('click', _previousStage);
            $("#next").bind('click', _nextStage);

            $("#faction").bind('change', _factionChanged);
            $(".raceselect").bind('change', _raceChanged);

            $(".selectpicker").selectpicker({
                showIcon: true
            });

            // Init
            _setStage(1);
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
            let raceId = parseInt($raceSelect.val());
            let $classSelect = $(".classselect").find("[data-id='" + $raceSelect.data('id') + "']");
            console.log($classSelect[0]);

            $classSelect.find('option').remove();

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
                                .replace('src=""', 'src="../../images/' + classDetail.iconfile.path + '"')
                                .replace('{text}', classDetail.name)
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
        }

        function _nextStage() {
            if (_currentStage < _stages.length) {
                _setStage(_currentStage + 1);
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
            _handleButtonVisibility();
        }

        function _handleButtonVisibility() {
            if (_currentStage === 1) {
                $("#previous").addClass('hidden');
            } else {
                $("#previous").removeClass('hidden');
            }

            if (_currentStage === _stages.length) {
                $("#next").addClass('hidden');
                $("#finish").removeClass('hidden');
            } else {
                $("#next").removeClass('hidden');
            } //
        }
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div id="setup_container" class="container">
        <div class="col-lg-12">
            <div class="col-lg-1">
                {!! Form::button('<i class="fa fa-backward"></i> ' . __('Previous'), ['id' => 'previous', 'class' => 'btn btn-info hidden']) !!}
            </div>
            <div class="col-lg-offset-10 col-lg-1">
                {!! Form::button('<i class="fa fa-forward"></i> ' . __('Next'), ['id' => 'next', 'class' => 'btn btn-info']) !!}
                {!! Form::submit('' . __('Finish'), ['id' => 'finish', 'class' => 'btn btn-success hidden']) !!}
            </div>
        </div>

        <hr />

        <div id="stage-1" class="col-lg-12">
            <h2>
                {{ __('Dungeon') }}
            </h2>
            <div class="form-group">
                {!! Form::label('dungeon', __('Select dungeon') . "*") !!}
                {!! Form::select('dungeon', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div id="stage-2" class="col-lg-12" style="display: none;">
            <h2>
                {{ __('Group composition') }}
            </h2>
            <div class="form-group">
                {!! Form::label('faction', __('Select faction')) !!}
                {{--array_combine because we want keys to be equal to values https://stackoverflow.com/questions/6175548/array-copy-values-to-keys-in-php--}}
                {!! Form::select('faction', array_combine(config('mpplnr.factions'), config('mpplnr.factions')), 0, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                <?php for($i = 1; $i <= config('mpplnr.party_size'); $i++){ ?>
                <div class="col-lg-2{{ $i === 1 ? ' col-lg-offset-1' : '' }}">
                    {!! Form::label('race[]', __('Party member #' . $i)) !!}
                    <select name="race[]" class="form-control selectpicker raceselect" data-id="{{$i}}">

                    </select>

                    {{--{!! Form::select('class[]', [-1 => __('Class...')], 0,--}}
                    {{--['id' => 'class_' . $i, 'class' => 'form-control selectpicker', 'data-id' => $i]) !!}--}}
                    <select name="class[]" class="form-control selectpicker classselect" data-id="{{$i}}">

                    </select>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    {{--<div id="stage-3" class="col-lg-12" style="display: none;">--}}
        {{--<div id="map_container">--}}
            {{--@include('common.maps.map', [--}}
                {{--'admin' => false,--}}
                {{--'dungeons' => \App\Models\Dungeon::all(),--}}
                {{--'dungeonSelect' => false,--}}
                {{--'manualInit' => true--}}
            {{--])--}}
        {{--</div>--}}
    {{--</div>--}}


    <div id="template_dropdown_icon" style="display: none;">
        <span>
            <img src="" class="class_icon"/> {text}
        </span>
    </div>

    {!! Form::close() !!}
@endsection

