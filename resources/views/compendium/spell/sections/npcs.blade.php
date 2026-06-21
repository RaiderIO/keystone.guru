<?php

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Spell           $spell
 * @var Collection<int, Npc> $npcs
 */
?>
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.spell.sections.npcs.title') }}</h4>
        @if($npcs->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm mt-2">
                    <thead>
                    <tr>
                        <th>{{ __('view_compendium.spell.sections.npcs.header_name') }}</th>
                        <th>{{ __('view_compendium.spell.sections.npcs.header_dungeons') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($npcs as $npc)
                        <tr>
                            <td>@include('common.npc.link', ['npc' => $npc])</td>
                            <td>{{ $npc->dungeons->map(fn($d) => __($d->name))->join(', ') ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">{{ __('view_compendium.spell.sections.npcs.empty') }}</p>
        @endif
    </div>
</div>
