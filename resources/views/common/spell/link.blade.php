<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 * @var int   $size
 */
$size ??= 20;
?>
<a href="{{ route('spell.compendium.show', $spell->id) }}"><img src="{{ $spell->icon_url }}"
         width="{{ $size }}" height="{{ $size }}"
         class="mr-1" loading="lazy" alt=""/>{{ __($spell->name) }}</a>
