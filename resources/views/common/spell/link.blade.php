<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 * @var int   $size
 */
$size ??= 20;
?>
{{-- Our own tooltip when we rendered a description for this spell, Wowhead's when we could not (#3951) --}}
<a href="{{ route('spell.compendium.show', $spell) }}"
   @if($spell->description !== null) data-spell-description="{{ $spell->description }}" data-spell-name="{{ __($spell->name) }}"
   @else data-wowhead="{{ $spell->wowhead_tooltip_data }}" data-wh-iconize-link="false" @endif><img src="{{ $spell->icon_url }}"
         width="{{ $size }}" height="{{ $size }}"
         class="me-1" loading="lazy" alt=""/>{{ __($spell->name) }}</a>
