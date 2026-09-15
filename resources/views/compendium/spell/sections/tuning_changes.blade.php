<?php

use App\Models\Spell\SpellTuningChange;
use Illuminate\Support\Collection;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 * One block per client build that changed this spell's numbers, newest build first.
 *
 * @var Collection<string, Collection<int, SpellTuningChange>> $tuningChangesByBuild keyed by to_build, newest first
 */
?>
@if($tuningChangesByBuild->isEmpty())
    <p class="text-muted mb-0">{{ __('view_compendium.spell.sections.tuning_changes.empty') }}</p>
@else
    @foreach($tuningChangesByBuild as $toBuild => $changes)
        <?php /** @var Collection<int, SpellTuningChange> $changes */ ?>
        <div class="compendium_tuning_build {{ $loop->last ? '' : 'mb-3' }}">
            <div class="compendium_record_label_sub">
                {{ __('view_compendium.spell.sections.tuning_changes.build_header', [
                    'from' => $changes->first()->from_build,
                    'to'   => $toBuild,
                ]) }}
            </div>
            @include('compendium.sections.tuning_change_list', [
                'changes'  => $changes,
                'emptyKey' => 'view_compendium.spell.sections.tuning_changes.empty',
            ])
        </div>
    @endforeach
@endif
