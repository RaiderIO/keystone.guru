@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Mapping')])

@section('header-title', __('Mapping progress'))

@section('content')
    <h2>{{ __('Enemy forces mapping progress') }}</h2>
    @foreach(\App\Models\Dungeon::active()->get() as $dungeon )
        <?php /** @var $dungeon \App\Models\Dungeon */ ?>
        <div class="row">
            <div class="col-lg-2">
                {{ $dungeon->name }}
            </div>
            <div class="col-lg-4">
                <div class="progress">
                    @php($percent = $dungeon->enemy_forces_mapped_status['percent'])
                    @php($total = $dungeon->enemy_forces_mapped_status['total'])
                    @php($curr = $total - $dungeon->enemy_forces_mapped_status['unmapped'])
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-left">
                        {{ __('Enemy forces') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="progress">
                    <?php
                    $totalEnemies = 0;
                    $totalUnassignedEnemies = 0;
                    $hasTeemingEnemy = false;
                    foreach ($dungeon->floors as $floor) {
                        /** @var $floor \App\Models\Floor */
                        $totalEnemies += $floor->enemies->count();
                        $totalUnassignedEnemies += $floor->enemies->whereIn('npc_id', [-1, 0])->count();
                        $hasTeemingEnemy = $hasTeemingEnemy || $floor->enemies->where('teeming', 'visible')->count() > 0;
                    }
                    ?>
                    @php($curr = $totalEnemies - $totalUnassignedEnemies)
                    @php($percent = ($curr / $totalEnemies) * 100)
                    @php($total = $totalEnemies)
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-left">
                        {{ __('NPCs assigned') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                {{ __('Teeming') }}:
                {!! Form::checkbox($dungeon->name . '_teeming', 1, $hasTeemingEnemy, ['disabled' => 'disabled']) !!}
            </div>
        </div>
    @endforeach
    <p>
        Enemies whose enemy forces have not been mapped are colored pink on the map. Do you know how many enemy forces
        these enemies give? Please contact me!
    </p>

    <p class="mt-2">
    <h2>Known mapping errors</h2>
    <h3>General</h3>
    <ul>
        <li>
            Some enemies with patrols are only there on Teeming week. At the moment it's not possible to mark a patrol
            as Teeming only, a solution will be brought for this at a later stage.
        </li>
    </ul>
    <h3>Waycrest Manor</h3>
    <ul>
        <li>
            On Teeming weeks, Infected Peasants spawn 3 Devouring Maggots instead of 2. This leads to incorrect mob
            counts as Devouring Maggots give 1 enemy force each, the spawned ones too. At the moment there's no
            technical solution for this, I'll have to make something for this. Infected Peasants currently give 6 enemy
            forces (4 from the initial kill, 2 are implied with Devouring Maggot kills that spawn from them). Compensate
            for 1 more Maggot per Peasant kill.
        </li>
    </ul>
    Also see <a href="https://github.com/Wotuu/keystone.guru/issues/33">our Github issue</a>
    for more mapping issues.
    </p>
@endsection