@inject('subcreationTierListService', 'App\Service\Subcreation\AffixGroupEaseTierServiceInterface')
<?php
/** @var $subcreationTierListService \App\Service\Subcreation\AffixGroupEaseTierServiceInterface */
/** @var $tier string */
/** @var $dungeon \App\Models\Dungeon */
/** @var $affixgroup \App\Models\AffixGroup */

// Users may write their own tiers if they received it from a batch call for example
$tier = $tier ?? $subcreationTierListService->getTierForAffixAndDungeon($affixgroup, $dungeon);
$url = $url ?? null;
?>
@if($tier !== null)
    <a href="{{ $url ?? 'https://mplus.subcreation.net' }}" target="_blank" rel="noopener noreferrer">
    <span class="tier {{ strtolower($tier) }}"
          @if( $url === null )
          data-toggle="tooltip"
          title="{{ sprintf(
            __('views/common.dungeonroute.tier.data_by_subcreation'),
                $affixgroup->getTextAttribute()
            )}}"
          @endif
    >
        {{ $tier }}
    </span>
    </a>
@endif