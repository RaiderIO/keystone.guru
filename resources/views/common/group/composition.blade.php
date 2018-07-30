<?php
/** @var \App\Models\DungeonRoute $model */
$factions = \App\Models\Faction::with('iconfile')->get();
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get();
$classes = \App\Models\CharacterClass::with('iconfile')->get();
?>

@section('head')
    @parent

    <style>
        @foreach($factions as $faction)
        .{{ strtolower($faction->name) }}    {
            color: {{ $faction->color }};
            font-weight: bold;
        }
        @endforeach

    </style>
@endsection

@section('scripts')
    @parent

    <script>
        let _factions = {!! $factions !!};
        let _racesClasses = {!! $racesClasses !!};
        let _classDetails = {!! $classes !!};

        // Defined in dungeonroutesetup.js
        $(function () {
            $("#reload_button").bind('click', function (e) {
                e.preventDefault();
                _loadDungeonRouteDefaults();
            });

            // Force population of the race boxes
            _factionChanged();

            _loadDungeonRouteDefaults();
        });

        function _loadDungeonRouteDefaults() {
                    @isset($dungeonroute)

            let faction = '{{ $dungeonroute->faction_id }}';
            let races = {!! $dungeonroute->races !!};
            let classes = {!! $dungeonroute->classes !!};

            let $faction = $("#faction_id");
            $faction.val(faction);
            // Have to manually trigger change..
            $faction.trigger('change');

            let $racesSelects = $(".raceselect select");
            let $classSelects = $(".classselect select");

            // For each race
            for (let i = 0; i < races.length; i++) {
                let race = races[i];
                let $raceSelect = $($racesSelects[i]);
                $raceSelect.val(race.id);
                // Have to manually trigger change..
                $raceSelect.trigger('change');
            }

            // For each class
            for (let i = 0; i < classes.length; i++) {
                let characterClass = classes[i];
                let $classSelect = $($classSelects[i]);
                $classSelect.val(characterClass.id);
                // Have to manually trigger change..
                $classSelect.trigger('change');
            }

            // Refresh new values and show em properly
            $('.selectpicker').selectpicker('refresh');

            @endisset
        }
    </script>
@endsection
<div class="col-lg-12">
    <div class="col-lg-offset-5 col-lg-2">
        <div class="form-group">
            {!! Form::label('faction_id', __('Select faction')) !!}
            {{--array_combine because we want keys to be equal to values https://stackoverflow.com/questions/6175548/array-copy-values-to-keys-in-php--}}
            {!! Form::select('faction_id', \App\Models\Faction::all()->pluck('name', 'id'), 0, ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    @isset($dungeonroute)
        <div class="col-lg-offset-4 col-lg-1">
            <div class="form-group">
                <button id="reload_button" class="btn btn-warning">
                    <i class="fa fa-undo"></i> {{ __('Reset') }}
                </button>
            </div>
        </div>
    @endisset
</div>
<div class="col-lg-12">
    <?php for($i = 1; $i <= config('mpplnr.party_size'); $i++){ ?>
    <div class="col-lg-2{{ $i === 1 ? ' col-lg-offset-1' : '' }}">
        <div class="form-group">
            {!! Form::label('race[]', __('Party member #' . $i)) !!}
            <select name="race[]" id="race_{{ $i }}" class="form-control selectpicker raceselect" data-id="{{$i}}">

            </select>
        </div>

        <div class="form-group">
            {{--{!! Form::select('class[]', [-1 => __('Class...')], 0,--}}
            {{--['id' => 'class_' . $i, 'class' => 'form-control selectpicker', 'data-id' => $i]) !!}--}}
            <select name="class[]" class="form-control selectpicker classselect" data-id="{{$i}}">

            </select>
        </div>
    </div>
    <?php } ?>
</div>

@foreach($factions as $faction)
    <div id="template_faction_dropdown_icon_{{ strtolower($faction->name) }}" style="display: none;">
        <span class="{{ strtolower($faction->name) }}">
            <img src="{{ Image::url($faction->iconfile->getUrl(), 32, 32) }}"
                 class="select_icon faction_icon"/> {{ $faction->name }}
        </span>
    </div>
@endforeach

@foreach( $classes as $class)
    <div id="template_class_dropdown_icon_{{ $class->key }}" style="display: none;">
    <span class="{{ $class->key }}">
        <img src="{{ Image::url($class->iconfile->getUrl(), 32, 32) }}" class="select_icon class_icon"/> {{ $class->name }}
    </span>
    </div>
@endforeach