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
            <i class="fab fa-battle-net" aria-hidden="true"></i>
            {{ __('view_common.forms.oauth.continue_with_battlenet') }}
        </button>
    </div>
</form>

<div class="mb-3">
    <a href="{{ route('login.discord') }}"
       class="btn btn-oauth w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="fab fa-discord" aria-hidden="true"></i>
        {{ __('view_common.forms.oauth.continue_with_discord') }}
    </a>
</div>

{{-- Google's branding guidelines require its own button artwork rather than our .btn-oauth; one
     variant per theme, the other hidden by the theme stylesheet --}}
<div class="mb-3 d-flex justify-content-center">
    <a href="{{ route('login.google') }}" class="btn-oauth-google">
        <img src="{{ ksgAssetImage('oauth/branding_guideline_sample_lt_sq_lg.png') }}"
             class="btn-oauth-google-light"
             width="354" height="80"
             alt="{{ __('view_common.forms.oauth.sign_in_with_google') }}"/>
        <img src="{{ ksgAssetImage('oauth/branding_guideline_sample_dk_sq_lg.png') }}"
             class="btn-oauth-google-dark"
             width="354" height="80"
             alt="{{ __('view_common.forms.oauth.sign_in_with_google') }}"/>
    </a>
</div>
