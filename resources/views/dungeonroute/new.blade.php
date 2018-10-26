@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)


@section('scripts')
    @parent

    <script>
        $(function () {
            let $dungeonIdSelect = $('#dungeon_id_select');
            $dungeonIdSelect.bind('change', function () {
                let $factionWarning = $('#siege_of_boralus_faction_warning');
                if (parseInt($dungeonIdSelect.val()) === {{ \App\Models\Dungeon::siegeOfBoralus()->get()->first()->id }} ) {
                    $factionWarning.show();
                } else {
                    $factionWarning.hide();
                }
            });
        })
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div class="container {{ isset($model) ? 'hidden' : '' }}">
        <h3>
            {{ __('General') }}
        </h3>
        <div class="form-group">
            {!! Form::label('dungeon_route_title', __('Title') . "*") !!}
            {!! Form::text('dungeon_route_title', '', ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
            {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['id' => 'dungeon_id_select', 'class' => 'form-control']) !!}
            <div id="siege_of_boralus_faction_warning" class="text-warning" style="display: none;">
                {{ __('Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.') }}
            </div>
        </div>
    <!--
        <div class="form-group">
            {!! Form::label('difficulty', __('Difficulty') . "*") !!}
    <?php $difficulty = config('keystoneguru.dungeonroute_difficulty'); ?>
    {!! Form::select('difficulty', array_combine($difficulty, $difficulty), null, ['class' => 'form-control']) !!}
            </div>
-->
        <div class="form-group">
            {!! Form::label('teeming', __('Teeming (check to change the dungeon to resemble Teeming week)')) !!}
            {!! Form::checkbox('teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
        </div>

        <h3>
            {{ __('Group composition (optional)') }}
        </h3>
        @include('common.group.composition')

        <h3>
            {{ __('Affixes (optional)') }}
        </h3>

        @include('common.group.affixes', ['teemingselector' => '#teeming'])

        @if(Auth::user()->hasPaidTier('unlisted-routes'))
            <h3>
                {{ __('Sharing') }}
            </h3>
            <div class="form-group">
                {!! Form::label('unlisted', __('Private (when checked, only people with the link can view your route)')) !!}
                {!! Form::checkbox('unlisted', 1, 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
        @endif

        @if(Auth::user()->hasRole('admin'))
            <h3>
                {{ __('Admin') }}
            </h3>
            <div class="form-group">
                {!! Form::label('demo', __('Mark as demo route')) !!}
                {!! Form::checkbox('demo', 1, 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
        @endif

        <div class="col-lg-12">
            <div class="form-group">
                {!! Form::submit(__('Create route'), ['class' => 'btn btn-info col-md-auto']) !!}
            </div>
        </div>
        @if(!Auth::user()->hasPaidTier('unlimited-routes'))
            {{ sprintf(__('You may create %s more route(s).'),
                Auth::user()->getRemainingRouteCount()
            ) }}

            <a href="https://www.patreon.com/keystoneguru">
                <i class="fab fa-patreon"></i> {{ __('Patrons have no limits!') }}
            </a>
        @endif
    </div>

    {!! Form::close() !!}
@endsection

