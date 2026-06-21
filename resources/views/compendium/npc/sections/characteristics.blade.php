<?php

use App\Models\Characteristic;
use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

/**
 * @var Npc                          $npc
 * @var Collection<int, Characteristic>   $allCharacteristics
 */
?>
{{-- Characteristics --}}
<?php $npcCharacteristicIds = $npc->characteristics->pluck('id')->flip(); ?>
<div class="row mb-4">
    <div class="col">
        <div class="row no-gutters">
            <div class="col-auto">
                <h4>{{ __('view_compendium.npc.sections.characteristics.title') }}</h4>
            </div>
            <div class="col ml-1">
                <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top"
                   title="{{ __('view_compendium.npc.sections.characteristics.tooltip') }}"></i>
            </div>
        </div>
        <div class="d-flex flex-wrap">
            @foreach($allCharacteristics as $characteristic)
                <?php /** @var Characteristic $characteristic */ ?>
                <?php $hasCharacteristic = $npcCharacteristicIds->has($characteristic->id); ?>
                <div class="mr-2 mb-2 text-center" style="width: 48px;"
                     data-toggle="tooltip" data-placement="top" title="{{ __($characteristic->name) }}">
                    <img src="{{ ksgAssetImage(sprintf('spells/%s.jpg', $characteristic->icon_name)) }}"
                         width="36" height="36"
                         loading="lazy"
                         class="rounded"
                         style="{{ $hasCharacteristic ? '' : 'filter: grayscale(100%); opacity: 0.35;' }}"
                         alt="{{ __($characteristic->name) }}"/>
                </div>
            @endforeach
        </div>
    </div>
</div>
