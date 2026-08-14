<?php

use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, GameServerRegion> $allRegions
 * @var string                            $idPrefix
 */

// This partial renders up to three times in one document (the page form plus both modals), so ids
// cannot be derived from $modalClass alone - the caller passes a prefix unique to its own form.
$idPrefix ??= '';

// `world` is a Keystone.guru region row with no Battle.net OAuth endpoint behind it
$battleNetRegions = $allRegions->filter(
    static fn(GameServerRegion $region): bool => in_array($region->short, GameServerRegion::BATTLE_NET_REGIONS, true)
);
?>

{{-- A GET form, so the selected region ends up as ?region=<short> - exactly what the per-region
     links used to carry, without needing six sibling buttons to express it --}}
<form method="GET" action="{{ route('login.battlenet') }}">
    <div class="mb-3">
        <label for="{{ $idPrefix }}oauth_battlenet_region" class="form-label">
            {{ __('view_common.forms.oauth.battlenet_region') }}
        </label>

        {{ html()->select('region', $battleNetRegions->mapWithKeys(function (GameServerRegion $region) {
    return [$region->short => __($region->name)];
})->toArray())->id($idPrefix . 'oauth_battlenet_region')->value(GameServerRegion::DEFAULT_REGION)->class('form-select') }}
    </div>

    <div class="mb-3">
        <button type="submit"
                class="btn btn-oauth w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="fab fa-battle-net"></i>
            {{ __('view_common.forms.oauth.continue_with_battlenet') }}
        </button>
    </div>
</form>

<div class="mb-3">
    <a href="{{ route('login.discord') }}"
       class="btn btn-oauth w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="fab fa-discord"></i>
        {{ __('view_common.forms.oauth.continue_with_discord') }}
    </a>
</div>

<div class="mb-3">
    <a href="{{ route('login.google') }}"
       class="btn btn-oauth w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="fab fa-google"></i>
        {{ __('view_common.forms.oauth.continue_with_google') }}
    </a>
</div>
