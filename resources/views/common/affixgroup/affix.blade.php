<?php
/** @var $affix \App\Models\Affix */
$media = $media ?? 'lg';
$showText = $showText ?? true;
$dungeon = $dungeon ?? null;
?>

<div class="row no-gutters mt-2 ">
    <div
        class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->key) }} {{ $showText ? '' : 'mx-1' }} m-auto"
        data-toggle="tooltip"
        title="{{ __($affix->description) }}"
        style="height: 24px;">
    </div>
    @if($showText)
        <div class="col d-{{ $media }}-block d-none pl-1">
            {{ __($affix->name) }}
        </div>
    @endif
</div>
