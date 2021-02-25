@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('Mapping')])

@section('header-title', __('Mapping progress'))

@section('content')
    <h2>{{ __('Enemy forces mapping progress') }}</h2>
    <div class="row">
        <div class="col-lg-2 font-weight-bold">
            {{ __('Dungeon') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('Enemy forces assigned to NPCs ') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('Npcs assigned to enemies') }}
        </div>
        <div class="col-lg-2 font-weight-bold">
            {{ __('Teeming') }}
        </div>
    </div>
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
                    @php($percent = $totalEnemies === 0 ? 0 : ($curr / $totalEnemies) * 100)
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
                {!! Form::checkbox($dungeon->name . '_teeming', 1, $hasTeemingEnemy, ['disabled' => 'disabled']) !!}
            </div>
        </div>
    @endforeach
@endsection