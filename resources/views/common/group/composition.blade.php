<?php
/** @var \App\Models\DungeonRoute $model */
$factions = isset($factions) ? $factions : \App\Models\Faction::with('iconfile')->get();
$specializations = \App\Models\CharacterClassSpecialization::with('iconfile')->get();
$classes = \App\Models\CharacterClass::with('specializations')->get();
// @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get();
?>

@section('head')
    @parent

    <style>
        @foreach($factions as $faction)
        .{{ strtolower($faction->name) }}                 {
            color: {{ $faction->color }};
            font-weight: bold;
        }

        @endforeach

        .testtesty {

        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        let _factions = {!! $factions !!};
        let _specializations = {!! $specializations !!};
        // Clarity so that _classes is not a thing (conflicts with a programming class etc).
        let _classDetails = {!! $classes !!};
        let _races = {!! $racesClasses !!};

        // Defined in dungeonroutesetup.js
        $(function () {
            $("#reload_button").bind('click', function (e) {
                e.preventDefault();
                _loadDungeonRouteDefaults();
            });


            // Defined in groupcomposition.js
            initGroupComposition();

            _loadDungeonRouteDefaults();

            // $('.selectpicker').selectpicker({
            //     showIcon: true
            // });
        });

        function _loadDungeonRouteDefaults() {
                    @isset($dungeonroute)

            let faction = '{{ $dungeonroute->faction_id }}';
            let specializations = {!! $dungeonroute->specializations !!};
            let classes = {!! $dungeonroute->classes !!};
            let races = {!! $dungeonroute->races !!};

            let $faction = $("#faction_id");
            $faction.val(faction);
            // Have to manually trigger change..
            $faction.trigger('change');

            let $specializationsSelects = $(".specializationselect select");
            let $racesSelects = $(".raceselect select");
            let $classSelects = $(".classselect select");

            // For each specialization
            for (let i = 0; i < specializations.length; i++) {
                let characterSpecialization = specializations[i];
                let $specializationSelect = $($specializationsSelects[i]);
                $specializationSelect.val(characterSpecialization.id);
                // Have to manually trigger change..
                $specializationSelect.trigger('change');
            }

            // For each class
            for (let i = 0; i < classes.length; i++) {
                let characterClass = classes[i];
                let $classSelect = $($classSelects[i]);
                $classSelect.val(characterClass.id);
                // Have to manually trigger change..
                $classSelect.trigger('change');
            }

            // For each race
            for (let i = 0; i < races.length; i++) {
                let race = races[i];
                let $raceSelect = $($racesSelects[i]);
                $raceSelect.val(race.id);
                // Have to manually trigger change..
                $raceSelect.trigger('change');
            }

            @endisset

            _refreshSelectPicker();
        }
    </script>
@endsection
<div class="row">
    <div class="col-lg-2 offset-lg-5">
        <div class="form-group">
            {!! Form::label('faction_id', __('Faction')) !!}
            {{--array_combine because we want keys to be equal to values https://stackoverflow.com/questions/6175548/array-copy-values-to-keys-in-php--}}
            {!! Form::select('faction_id', $factions->pluck('name', 'id'), 0, ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    @isset($dungeonroute)
        <div class="offset-lg-4 col-lg-1">
            <div class="form-group">
                <button id="reload_button" class="btn btn-warning">
                    <i class="fas fa-undo"></i> {{ __('Reset') }}
                </button>
            </div>
        </div>
    @endisset
</div>
<div class="row">
    <?php for($i = 1; $i <= config('keystoneguru.party_size'); $i++){ ?>
    <div class="col pl-1 pr-1">

        <div class="form-group">
            {!! Form::label('specialization[]', __('Party member #' . $i)) !!}
            <select data-live-search="true" name="specialization[]"
                    class="form-control selectpicker specializationselect" data-id="{{$i}}">

            </select>
        </div>

        <div class="form-group">
            <select name="class[]" class="form-control selectpicker classselect" data-id="{{$i}}">

            </select>
        </div>

        <div class="form-group">
            <select name="race[]" id="race_{{ $i }}" class="form-control selectpicker raceselect" data-id="{{$i}}">

            </select>
        </div>
    </div>
    <?php } ?>
</div>

<script id="composition_icon_option_template" type="text/x-handlebars-template">
    <div class="row no-gutters">
        <div class="col-auto select_icon class_icon @{{ css_class }}" style="height: 24px;">
        </div>
        <div class="col pl-1">
            @{{ name }}
        </div>
    </div>
</script>