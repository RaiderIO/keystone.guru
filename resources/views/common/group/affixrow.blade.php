<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;

/**
 * @var AffixGroup  $affixGroup
 * @var Season|null $season
 * @var string      $expansionKey
 */

$isTeeming  = $affixGroup->hasAffix(Affix::AFFIX_TEEMING);
$cssClasses ??= '';
?>
<div
    class="row affix_list_row expansion {{$expansionKey}}
    {{ $isTeeming ? 'affix_row_teeming' : 'affix_row_no_teeming' }}
    {{ isset($season) ? 'season season-' . $season->id : '' }}
    "
    {{ $isTeeming ? 'style="display: none;"' : '' }} data-id="{{ $affixGroup->id }}">
    <?php
    $count = 0;
    foreach ($affixGroup->affixes as $affix){
        $last = count($affixGroup->affixes) - 1 === $count;
        ?>
    <div class="col col-md pr-0 affix_row">
        <div class="row no-gutters">
            <div class="col-auto select_icon class_icon affix_icon_{{ $affix->image_name }}"
                 data-toggle="tooltip"
                 title="{{ __($affix->description) }}"
                 style="height: 24px;">
            </div>
            @if($names)
                <div class="col d-md-block d-none pl-1">
                    @if($last && $affixGroup->seasonal_index !== null)
                        {{ sprintf(__('affixes.seasonal_index_preset'), __($affix->name), $affixGroup->seasonal_index + 1) }}
                    @else
                        {{ __($affix->name) }}
                    @endif
                </div>
            @endif
        </div>
    </div>
        <?php ++$count;
    }
    ?>
    <span class="col col-md-auto text-right pl-0">
        <span class="check" style="visibility: hidden;">
            <i class="fas fa-check"></i>
        </span>
    </span>
</div>
