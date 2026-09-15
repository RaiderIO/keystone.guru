<?php

use App\Models\Characteristic;
use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

/**
 * The section title + tooltip live in the parent record section's label rail (see show.blade.php).
 *
 * @var Npc                             $npc
 * @var Collection<int, Characteristic> $allCharacteristics
 */

$npcCharacteristicIds = $npc->characteristics->pluck('id')->flip();

[$active, $inactive] = $allCharacteristics->partition(fn(Characteristic $characteristic) => $npcCharacteristicIds->has($characteristic->id));
?>
@if($active->isNotEmpty())
    <div class="compendium_characteristics">
        @foreach($active as $characteristic)
            <?php /** @var Characteristic $characteristic */ ?>
            <span class="compendium_characteristic">
                <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $characteristic->icon_name)) }}"
                     width="28" height="28"
                     loading="lazy"
                     alt="{{ __($characteristic->name) }}"/>
                {{ __($characteristic->name) }}
            </span>
        @endforeach
    </div>
@else
    <p class="text-muted mb-0">{{ __('view_compendium.npc.sections.characteristics.empty') }}</p>
@endif

@if($inactive->isNotEmpty())
    <div class="compendium_characteristics_muted">
        <span class="compendium_characteristics_muted_label">{{ __('view_compendium.npc.sections.characteristics.not_observed') }}</span>
        @foreach($inactive as $characteristic)
            <?php /** @var Characteristic $characteristic */ ?>
            <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $characteristic->icon_name)) }}"
                 width="22" height="22"
                 loading="lazy"
                 data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __($characteristic->name) }}"
                 alt="{{ __($characteristic->name) }}"/>
        @endforeach
    </div>
@endif
