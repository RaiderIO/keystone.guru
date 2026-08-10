<?php

use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 * @var int   $size
 */
$size ??= 20;
?>
<a href="{{ route('spell.compendium.show', $spell) }}" data-wowhead="spell={{ $spell->id }}" data-wh-iconize-link="false"><img src="{{ $spell->icon_url }}"
         width="{{ $size }}" height="{{ $size }}"
         class="me-1" loading="lazy" alt=""/>{{ __($spell->name) }}</a>
