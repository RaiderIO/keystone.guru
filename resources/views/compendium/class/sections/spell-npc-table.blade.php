<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * Spell → casting NPCs table, shared by the counter sections and the reflect section.
 *
 * @var Collection<int, Spell>                $tableSpells
 * @var Collection<int, Collection<int, Npc>> $tableNpcsBySpellId
 * @var string                                $tableSpellHeader Translation key for the spell column header
 * @var string                                $tableNpcsHeader  Translation key for the NPCs column header
 */
?>
<div class="table-responsive">
    <table class="table table-sm table-striped">
        <thead>
        <tr>
            <th width="35%">{{ __($tableSpellHeader) }}</th>
            <th width="65%">{{ __($tableNpcsHeader) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tableSpells as $tableSpell)
            <?php /** @var Spell $tableSpell */ ?>
            <?php $castingNpcs = $tableNpcsBySpellId->get($tableSpell->id, collect()); ?>
            <tr>
                <td>@include('common.spell.link', ['spell' => $tableSpell])</td>
                <td>
                    @if($castingNpcs->isEmpty())
                        <span class="text-muted">{{ __('view_compendium.class.show.no_npcs') }}</span>
                    @else
                        @foreach($castingNpcs as $castingNpc)
                            <?php /** @var Npc $castingNpc */ ?>
                            @include('common.npc.link', ['npc' => $castingNpc])@if(!$loop->last), @endif
                        @endforeach
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
