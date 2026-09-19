<?php

use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * The season choice of a collection as a radio button group, "No season (free-form)" included.
 *
 * @var string                  $idPrefix
 * @var Collection<int, Season> $seasons          With their expansion loaded.
 * @var int|null                $selectedSeasonId Null selects free-form.
 * @var bool                    $disabled
 */
?>
<div class="btn-group flex-wrap collection_season_radios" role="group">
    @foreach($seasons as $season)
        <input type="radio" name="season_id" id="{{ $idPrefix }}_{{ $season->id }}" class="btn-check"
               value="{{ $season->id }}" @checked($season->id === $selectedSeasonId) @disabled($disabled)>
        <label class="btn btn-secondary" for="{{ $idPrefix }}_{{ $season->id }}">
            <img src="{{ $season->expansion->getIconUrl() }}" alt="" class="collection_season_icon">
            {{ $season->name_long }}
        </label>
    @endforeach
    <input type="radio" name="season_id" id="{{ $idPrefix }}_none" class="btn-check"
           value="" @checked($selectedSeasonId === null) @disabled($disabled)>
    <label class="btn btn-secondary" for="{{ $idPrefix }}_none">
        {{ __('view_common.collection.details.season_none') }}
    </label>
</div>
