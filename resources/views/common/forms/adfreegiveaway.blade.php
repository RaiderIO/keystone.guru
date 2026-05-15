<?php

/** @var \App\Models\User $user */
$hasAdFree                      = $user->hasPatreonBenefit(\App\Models\Patreon\PatreonBenefit::AD_FREE);
$adFreeGiveaway                 = $user->patreonAdFreeGiveaway;
$hasAdFreeGiveawayByCurrentUser = $adFreeGiveaway !== null && $adFreeGiveaway->giver_user_id === Auth::id();
$hasAdFreeGiveawayByOtherUser   = $adFreeGiveaway !== null && $adFreeGiveaway->giver_user_id !== Auth::id();

?>
<div class="checkbox">
    <label>
        <input type="checkbox"
               class="ad_free_giveaway_checkbox"
               data-publickey="{{ $user->public_key }}"
            {{ $hasAdFree || $hasAdFreeGiveawayByOtherUser ? 'disabled' : '' }}
            {{ $hasAdFree || $hasAdFreeGiveawayByCurrentUser || $hasAdFreeGiveawayByOtherUser ? 'checked' : '' }}>
    </label>
</div>
