<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 * @var int   $size
 */
$size ??= 20;
?>
{{-- Our own tooltip when we rendered a description for this spell, Wowhead's when we could not (#3951).
     The format and its values go across rather than the finished sentence, so the numbers can be
     rescaled in the browser once a key level selector exists. --}}
<a href="{{ route('spell.compendium.show', $spell) }}"
   @if($spell->tooltip_data !== null) data-spell-tooltip="{{ json_encode($spell->tooltip_data) }}"
   @else data-wowhead="{{ $spell->wowhead_tooltip_data }}" data-wh-iconize-link="false" @endif><img src="{{ $spell->icon_url }}"
         width="{{ $size }}" height="{{ $size }}"
         class="me-1" loading="lazy" alt=""/>{{ __($spell->name) }}</a>
