<?php

use App\Models\Spell\Spell;

/**
 * The section title lives in the parent record section's label rail (see show.blade.php).
 *
 * @var Spell $spell
 */
?>
@foreach(preg_split('/\n{2,}/', $spell->description) as $paragraph)
    <p class="compendium_spell_description">{{ trim($paragraph) }}</p>
@endforeach
