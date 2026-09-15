<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 *
 * @var Spell                $spell
 * @var Collection<int, Npc> $npcs
 */
?>
@if($npcs->isNotEmpty())
    <div class="table-responsive">
        <table class="compendium_table">
            <thead>
            <tr>
                <th>{{ __('view_compendium.spell.sections.npcs.header_name') }}</th>
                <th>{{ __('view_compendium.spell.sections.npcs.header_dungeons') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($npcs as $npc)
                <tr>
                    <td class="text-nowrap">@include('common.npc.link', ['npc' => $npc])</td>
                    <td>{{ $npc->dungeons->map(fn($d) => __($d->name))->join(', ') ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted mb-0">{{ __('view_compendium.spell.sections.npcs.empty') }}</p>
@endif
