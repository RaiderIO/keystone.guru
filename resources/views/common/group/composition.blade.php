<?php
/** @var \App\Models\DungeonRoute $model */
$factions = isset($factions) ? $factions : \App\Models\Faction::all();
$specializations = \App\Models\CharacterClassSpecialization::all();
$classes = \App\Models\CharacterClass::with('specializations')->get();
// @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get();
// @TODO Upon form error, all specs/classes/races are cleared. It's really hard to get an error but it's gotta be handled at some point
?>

@section('head')
    @parent

    <style>
        @foreach($factions as $faction)
        .{{ strtolower($faction->name) }}                              {
            color: {{ $faction->color }};
            font-weight: bold;
        }

        @endforeach
    </style>
@endsection

@section('scripts')
    @parent

    <script id="test_script">

        let _factions = {!! $factions !!};
        let _specializations = {!! $specializations !!};
        // Clarity so that _classes is not a thing (conflicts with a programming class etc).
        let _classDetails = {!! $classes !!};
        let _races = {!! $racesClasses !!};

        let _oldFaction;
        let _oldSpecializations;
        let _oldClasses;
        let _oldRaces;

        // Defined in dungeonroutesetup.js
        $(function () {
            $("#reload_button").bind('click', function (e) {
                e.preventDefault();
                _loadDungeonRouteDefaults();
            });


            // Defined in groupcomposition.js
            initGroupComposition();
            <?php
                // @formatter:off
                // This piece of code (which does not format well at all, hence manual formatting) makes sure that if
                // we had an existing dungeon route we load their defaults, if we submitted something but failed to
                // validate its contents, we restore the sent data back to the form
                $oldFactionId = old('faction_id', null);
                if(isset($dungeonroute) || !is_null($oldFactionId)) {
                    if( isset($dungeonroute) ){ ?>

                    _oldFaction = '{{ $dungeonroute->faction_id }}';
                    _oldSpecializations = {!! $dungeonroute->specializations !!};
                    _oldClasses = {!! $dungeonroute->classes !!};
                    _oldRaces = {!! $dungeonroute->races !!};

                <?php } else {
                    // convert old values in a format we can read it in
                    $newSpecializations = [];
                    foreach (old('specialization', '') as $oldSpecialization) {
                        $newSpecializations[] = ['id' => $oldSpecialization];
                    }
                    $newClasses = [];
                    foreach (old('class', '') as $oldClass) {
                        $newClasses[] = ['id' => $oldClass];
                    }
                    $newRaces = [];
                    foreach (old('race', '') as $oldRace) {
                        $newRaces[] = ['id' => $oldRace];
                    }
                ?>

                    _oldFaction = '{{ old('faction_id', '') }}';
                    _oldSpecializations = {!! json_encode($newSpecializations)  !!};
                    _oldClasses = {!! json_encode($newClasses)  !!};
                    _oldRaces = {!! json_encode($newRaces)  !!};

            <?php } ?>
            _loadDungeonRouteDefaults();
            <?php }
        // @formatter:on
            ?>
        });
    </script>
@endsection

<div class="row">
    <div class="col-xl-2 offset-xl-5">
        <div class="form-group">
            {!! Form::label('faction_id', __('Faction')) !!}
            {{--array_combine because we want keys to be equal to values https://stackoverflow.com/questions/6175548/array-copy-values-to-keys-in-php--}}
            {!! Form::select('faction_id', $factions->pluck('name', 'id'), old('faction_id'), ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    @isset($dungeonroute)
        <div class="offset-lg-4 col-lg-1">
            <div class="form-group">
                <button id="reload_button" class="btn btn-warning">
                    <i class="fas fa-undo"></i> {{ __('Undo') }}
                </button>
            </div>
        </div>
    @endisset
</div>
<div class="row">
    <?php for($i = 1; $i <= config('keystoneguru.party_size'); $i++){ ?>
    <div class="col-md pl-1 pr-1">

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