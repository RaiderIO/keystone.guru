<?php
/** @var $affixGroupEaseTiersByAffixGroup \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroupEaseTier[] */
/** @var $tier string */
/** @var $dungeon \App\Models\Dungeon */
/** @var $affixgroup \App\Models\AffixGroup\AffixGroup */

// Users may write their own tiers if they received it from a batch call for example
$tier ??= optional(optional(optional($affixGroupEaseTiersByAffixGroup->get($affixgroup->id))->get($dungeon->id))->first())->tier;
$url  ??= null;
?>
@if($tier !== null)
    <a href="{{ $url ?? 'https://www.archon.gg/wow' }}" target="_blank" rel="noopener noreferrer">
    <span class="tier {{ strtolower($tier) }}"
          @if( $url === null )
              data-toggle="tooltip"
          title="{{ sprintf(
            __('views/common.dungeonroute.tier.data_by_archon_gg'),
                $affixgroup->getTextAttribute()
            )}}"
          @endif
    >
        {{ $tier }}
    </span>
    </a>
@endif
