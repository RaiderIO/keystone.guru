<?php

use App\Models\Dungeon;
use App\Models\Spell\Spell;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 * A one-column table earned nothing; the dungeons render as a chip list.
 *
 * @var Spell $spell
 */
?>
@if($spell->dungeons->isNotEmpty())
    <div class="compendium_identity_meta">
        @foreach($spell->dungeons as $dungeon)
            <?php /** @var Dungeon $dungeon */ ?>
            <span class="compendium_chip">{{ __($dungeon->name) }}</span>
        @endforeach
    </div>
@else
    <p class="text-muted mb-0">{{ __('view_compendium.spell.sections.dungeons.empty') }}</p>
@endif
