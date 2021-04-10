@inject('subcreationTierListService', 'App\Service\Subcreation\AffixGroupEaseTierServiceInterface')
<?php
/** @var $subcreationTierListService \App\Service\Subcreation\AffixGroupEaseTierServiceInterface */
/** @var $affixGroup \App\Models\AffixGroup */
$media = $media ?? 'lg';
$showText = $showText ?? true;
$class = $class ?? '';
$dungeon = $dungeon ?? null;
?>
<div class="row no-gutters {{ $class }}">
    <?php
    $affixIndex = 0;
    foreach($affixGroup->affixes as $affix) {
    $lastColumn = count($affixGroup->affixes) - 1 === $affixIndex;
    ?>
    <div class="col">
        <div class="row no-gutters mt-2">
            <div
                class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }} {{ $showText ? '' : 'mx-1' }}"
                data-toggle="tooltip"
                title="{{ $affix->description }}"
                style="height: 24px;">
            </div>
            @if($showText)
                <div class="col d-{{ $media }}-block d-none pl-1">
                    {{ $affix->name }}
                    {{--                @if($lastColumn && $affixGroup->season->presets > 0 )--}}
                    {{--                    {{ __(sprintf('preset %s', $affixGroup->season->getPresetAt($startDate))) }}--}}
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
        <?php $tier = $subcreationTierListService->getTierForAffixAndDungeon($affixGroup, $dungeon); ?>
        @if($tier !== null)
            <div class="col">
                <h5 class="font-weight-bold pl-1 mt-2">
                    @include('common.dungeonroute.tier', ['tier' => $tier])
                </h5>
            </div>
        @endif
    @endif
</div>