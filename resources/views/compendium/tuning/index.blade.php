<?php

use App\Models\Dungeon;
use App\Models\Spell\SpellTuningChange;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @var Dungeon|null                                                                                     $contextDungeon      null on the unscoped page
 * @var LengthAwarePaginator<int, array{from_build: string, to_build: string, to_build_number: int, spell_count: int}> $builds
 * @var array<string, Collection<int, SpellTuningChange>>                                                $changesByBuild      keyed by to_build
 * @var Collection<int, Dungeon>                                                                         $gameVersionDungeons
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbs'         => $contextDungeon === null ? 'compendium.tuning.index' : 'compendium.tuning',
    'breadcrumbsParams'   => $contextDungeon === null ? [] : [$contextDungeon],
    'title'               => __('view_compendium.tuning.index.title'),
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('compendium.tuning', ['dungeon' => $dungeon])
    ]),
])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            if (typeof $WowheadPower !== 'undefined') {
                $WowheadPower.refreshLinks();
            }
        });
    </script>
@endsection

@section('header-title')
    @if($contextDungeon === null)
        {{ __('view_compendium.tuning.index.header') }}
    @else
        {{ __('view_compendium.tuning.index.header_dungeon', ['dungeon' => __($contextDungeon->name)]) }}
    @endif
@endsection

@section('content')
    <p class="text-muted">{{ __('view_compendium.tuning.index.intro') }}</p>

    <div class="compendium_toolbar mb-3">
        @if($contextDungeon === null)
            <span class="compendium_chip compendium_chip--works">{{ __('view_compendium.tuning.index.all_dungeons') }}</span>
        @else
            <a href="{{ route('compendium.tuning.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-globe"></i> {{ __('view_compendium.tuning.index.show_all_dungeons') }}
            </a>
        @endif
    </div>

    @if($builds->isEmpty())
        <p class="text-muted">{{ __('view_compendium.tuning.index.empty') }}</p>
    @else
        @foreach($builds->items() as $build)
            <div class="compendium_record_section">
                <div class="compendium_record_label">
                    {{ __('view_compendium.tuning.index.build_title', ['build' => $build['to_build']]) }}
                    <div class="compendium_record_label_sub">
                        {{ __('view_compendium.tuning.index.build_subtitle', ['from' => $build['from_build']]) }}
                        &middot;
                        {{ trans_choice('view_compendium.tuning.index.changed_spells', $build['spell_count'], ['count' => $build['spell_count']]) }}
                    </div>
                </div>
                <div>
                    @include('compendium.sections.tuning_change_list', [
                        'changes'          => $changesByBuild[$build['to_build']],
                        'emptyKey'         => 'view_compendium.tuning.index.empty',
                        'showSpellSubject' => true,
                        'showDungeons'     => $contextDungeon === null,
                    ])
                </div>
            </div>
        @endforeach

        {{ $builds->links() }}
    @endif
@endsection
