<?php
/** @var $tier string */
/** @var $affixgroup \App\Models\AffixGroup */
?>
<a href="https://mplus.subcreation.net" target="_blank" rel="noopener noreferrer">
    <span class="tier {{ strtolower($tier) }}" data-toggle="tooltip"
          title="{{ sprintf(
            __('%s - data by https://mplus.subcreation.net'),
                $affixgroup->getTextAttribute()
            )}}">
        {{ $tier }}
    </span>
</a>