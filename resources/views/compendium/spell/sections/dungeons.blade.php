<?php

use App\Models\Dungeon;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Spell                    $spell
 * @var Collection<int, Dungeon> $spell ->dungeons
 */
?>
<div class="row mb-4">
    <div class="col">
        <h4>{{ __('view_compendium.spell.sections.dungeons.title') }}</h4>
        @if($spell->dungeons->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm mt-2">
                    <thead>
                    <tr>
                        <th>{{ __('view_compendium.spell.sections.dungeons.header_name') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($spell->dungeons as $dungeon)
                        <tr>
                            <td>{{ __($dungeon->name) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">{{ __('view_compendium.spell.sections.dungeons.empty') }}</p>
        @endif
    </div>
</div>
