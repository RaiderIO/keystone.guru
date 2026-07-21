<?php

use App\Models\Affix;

/** @var Affix $affix */
$media    ??= 'lg';
$showText ??= true;
$dungeon  ??= null;
?>

<div class="row g-0">
    <div
        class="col-auto select_icon class_icon affix_icon_{{ $affix->image_name }} {{ $showText ? '' : 'mx-1' }} m-auto"
        data-bs-toggle="tooltip"
        title="{{ __($affix->description) }}"
        style="height: 24px;">
    </div>
    @if($showText)
        <div class="col d-{{ $media }}-block d-none ps-1">
            {{ __($affix->name) }}
        </div>
    @endif
</div>
