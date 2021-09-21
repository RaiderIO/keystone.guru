<?php
/** @var \App\Models\DungeonRoute $model */
/** @var $specializations \Illuminate\Support\Collection|\App\Models\CharacterClassSpecialization[] */
/** @var $classes \Illuminate\Support\Collection|\App\Models\CharacterClass[] */
/** @var $racesClasses \Illuminate\Support\Collection|\App\Models\CharacterRace[] */

$factions = $factions ?? \App\Models\Faction::all();
// @TODO Upon form error, all specs/classes/races are cleared. It's really hard to get an error but it's gotta be handled at some point
?>
@include('common.general.inline', ['path' => 'common/group/composition',
'options' => [
    'factions'         => $factions,
    'specializations'  => $specializations,
    'classDetails'     => $classes,
    'races'            => $racesClasses,
]])

@section('head')
    @parent

    <style>
        @foreach($factions as $faction)
        .{{ strtolower($faction->key) }}                                 {
            color: {{ $faction->color }};
            font-weight: bold;
        }
        @endforeach
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        let _oldFaction;
        let _oldSpecializations;
        let _oldClasses;
        let _oldRaces;

        // Defined in groupcomposition.js
        $(function () {


            <?php
                // @formatter:off
                // This piece of code (which does not format well at all, hence manual formatting) makes sure that if
                // we had an existing dungeon route we load their defaults, if we submitted something but failed to
                // validate its contents, we restore the sent data back to the form
                $oldFactionId = old('faction_id', null);
                if(isset($dungeonroute) || !is_null($oldFactionId)) {
                if( isset($dungeonroute) ){ ?>

                _oldFaction = '{{ $dungeonroute->faction_id }}';
            _oldSpecializations = {!! $dungeonroute->specializations ?? collect() !!};
            _oldClasses = {!! $dungeonroute->classes ?? collect() !!};
            _oldRaces = {!! $dungeonroute->races ?? collect() !!};

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


            <?php
            /** If collapseSelector is set, only load this when we're actually opening the collapseSelector to speed up loading. */
            if( isset($collapseSelector) ){ ?>
            $('{{$collapseSelector}}').on('shown.bs.collapse', function () {
                <?php } ?>
                let composition = _inlineManager.getInlineCode('common/group/composition');
                composition._loadDungeonRouteDefaults();

                <?php if( isset($collapseSelector) ){ ?>
            });
            <?php } ?>

            <?php
            }
            // @formatter:on
            ?>
        });
    </script>
@endsection

<div class="row">
    <div class="col-md-4 offset-md-4">
        <div class="form-group">
            {!! Form::label('faction_id', __('views/common.group.composition.faction')) !!}
            {{--array_combine because we want keys to be equal to values https://stackoverflow.com/questions/6175548/array-copy-values-to-keys-in-php--}}
            {!! Form::select('faction_id', $factions->pluck('name', 'id'), old('faction_id'), ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    @isset($dungeonroute)
        <div class="col-md-1">
            <div class="form-group">
                <button id="reload_button" class="btn btn-warning">
                    <i class="fas fa-undo"></i> {{ __('views/common.group.composition.undo') }}
                </button>
            </div>
        </div>
    @endisset
</div>
<div class="row">
    <?php for($i = 1; $i <= config('keystoneguru.party_size'); $i++){ ?>
    <div class="col-md pl-1 pr-1">

        <div class="form-group">
            {!! Form::label('specialization[]', sprintf(__('views/common.group.composition.party_member_nr'), $i)) !!}
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
