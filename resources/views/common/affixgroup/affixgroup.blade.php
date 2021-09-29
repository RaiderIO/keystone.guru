<?php
/** @var $affixgroup \App\Models\AffixGroup */
$media = $media ?? 'lg';
$showText = $showText ?? true;
$class = $class ?? '';
$dungeon = $dungeon ?? null;
$highlight = $highlight ?? false;
?>
<div class="row no-gutters px-1 affix_group_row {{ $highlight ? 'current' : '' }} {{ $class }}">
    <?php
    $affixIndex = 0;
    foreach($affixgroup->affixes as $affix) {
    $lastColumn = count($affixgroup->affixes) - 1 === $affixIndex;
    ?>
    <div class="col">
        <div class="row no-gutters mt-2 ">
            <div
                class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->key) }} {{ $showText ? '' : 'mx-1' }}"
                data-toggle="tooltip"
                title="{{ __($affix->description) }}"
                style="height: 24px;">
            </div>
            @if($showText)
                <div class="col d-{{ $media }}-block d-none pl-1">
                    {{ __($affix->name) }}
                    {{--                @if($lastColumn && $affixgroup->season->presets > 0 )--}}
                    {{--                    {{ __(sprintf('preset %s', $affixgroup->season->getPresetAt($startDate))) }}--}}
                    {{--                @endif--}}
                </div>
            @endif
        </div>
    </div>
    <?php
    $affixIndex++;
    }
    ?>
    @if($dungeon instanceof \App\Models\Dungeon)
        <div class="col">
            <h5 class="font-weight-bold pl-1 mt-2">
                @include('common.dungeonroute.tier', ['affixgroup' => $affixgroup, 'dungeon' => $dungeon])
            </h5>
        </div>
    @endif
</div>
