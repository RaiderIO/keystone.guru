<?php
    /** @var $affixGroup \App\Models\AffixGroup */
?>
<div class="row no-gutters">
    <?php
    $affixIndex = 0;
    foreach($affixGroup->affixes as $affix) {
    $lastColumn = count($affixGroup->affixes) - 1 === $affixIndex;
    ?>
    <div class="col">
        <div class="row no-gutters mt-2">
            <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                 data-toggle="tooltip"
                 title="{{ $affix->description }}"
                 style="height: 24px;">
            </div>
            <div class="col d-lg-block d-none pl-1">
                {{ $affix->name }}
{{--                @if($lastColumn && $affixGroup->season->presets > 0 )--}}
{{--                    {{ __(sprintf('preset %s', $affixGroup->season->getPresetAt($startDate))) }}--}}
{{--                @endif--}}
            </div>
        </div>
    </div>
    <?php
    $affixIndex++;
    }
    ?>
</div>