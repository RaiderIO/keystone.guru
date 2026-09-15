<?php

use Illuminate\Support\Carbon;

/**
 * When a client build went live, as shown next to that build on the tuning pages.
 *
 * @var Carbon $releasedAt UTC
 */
?>
<time datetime="{{ $releasedAt->toIso8601ZuluString() }}">{{ __('view_compendium.sections.tuning_build_released_at.went_live', ['date' => $releasedAt->format('M j, Y')]) }}</time>
