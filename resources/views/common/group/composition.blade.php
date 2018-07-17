<?php
/** @var \App\Models\DungeonRoute $model */
$racesClasses = \App\Models\CharacterRace::with(['classes:character_classes.id'])->get()->toArray();
$classes = \App\Models\CharacterClass::with('iconfile')->get()->toArray();
?>

@section('head')
    @parent

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

        // Defined in dungeonroutesetup.js
        $(function () {
            // Force population of the race boxes
            _factionChanged();
        });
    </script>
@endsection

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

<div id="template_dropdown_icon" style="display: none;">
        <span>
            <img src="" class="class_icon"/> {text}
        </span>
</div>