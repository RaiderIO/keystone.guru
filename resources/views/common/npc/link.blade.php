<?php

use App\Models\Npc\Npc;
use App\Models\MapIconType;

/**
 * @var Npc $npc
 * @var int $size
 */
$size ??= 20;
?>
<a href="{{ route('npc.compendium.show', $npc) }}">
    @if($npc->enemy_portrait_url)
        <img src="{{ ksgAsset($npc->enemy_portrait_url) }}"
             width="{{ $size }}" height="{{ $size }}"
             class="rounded me-1" loading="lazy" alt=""/>
    @endif
    @if($npc->isBoss())
        <img src="{{ ksgAssetImage(sprintf('mapicon/%s.png', MapIconType::MAP_ICON_TYPE_RAID_MARKER_SKULL))}}"
             alt="__('view_common.npc.link.boss')" width="16" height="16" class="me-1"
             title="{{ __('view_common.npc.link.boss') }}" data-bs-toggle="tooltip"/>
    @endif
    {{ __($npc->name) }}
</a>
