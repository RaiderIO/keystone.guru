@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.mapping.title')])

@section('header-title', __('views/misc.mapping.header'))

@section('content')
    <h2>{{ __('views/misc.mapping.enemy_forces_mapping_progress') }}</h2>
    <div class="row">
        <div class="col-lg-2 font-weight-bold">
            {{ __('views/misc.mapping.dungeon') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('views/misc.mapping.enemy_forces_assigned') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('views/misc.mapping.npcs_assigned_to_enemies') }}
        </div>
        <div class="col-lg-2 font-weight-bold">
            {{ __('views/misc.mapping.teeming') }}
        </div>
    </div>
    @foreach(\App\Models\Dungeon::active()->get() as $dungeon )
        <?php /** @var $dungeon \App\Models\Dungeon */ ?>
        <div class="row">
            <div class="col-lg-2">
                {{ __($dungeon->name) }}
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
                        {{ __('views/misc.mapping.enemy_forces') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
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
                        $totalEnemies           += $floor->enemies->count();
                        $totalUnassignedEnemies += $floor->enemies->whereIn('npc_id', [-1, 0])->count();
                        $hasTeemingEnemy        = $hasTeemingEnemy || $floor->enemies->where('teeming', 'visible')->count() > 0;
                    }
                    ?>
                    @php($curr = $totalEnemies - $totalUnassignedEnemies)
                    @php($percent = $totalEnemies === 0 ? 0 : ($curr / $totalEnemies) * 100)
                    @php($total = $totalEnemies)
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-left">
                        {{ __('views/misc.mapping.npcs_assigned') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                {!! Form::checkbox($dungeon->key . '_teeming', 1, $hasTeemingEnemy, ['disabled' => 'disabled']) !!}
            </div>
        </div>
    @endforeach
@endsection
