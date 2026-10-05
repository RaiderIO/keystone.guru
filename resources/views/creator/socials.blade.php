<?php

use App\Models\UserSocialLink;
use Illuminate\Support\Collection;

/**
 * A creator's social links as a row of round icon links, on the directory card and the profile hero.
 *
 * @var Collection<int, UserSocialLink> $socialLinks
 * @var string                          $class       Where the row sits: creator_card_socials or creator_hero_socials.
 */
?>
@if($socialLinks->isNotEmpty())
    <div class="creator_socials {{ $class }}">
        @foreach($socialLinks as $socialLink)
            <?php $socialLinkLabel = __('view_profile.view.social_link', [
                'platform' => __(sprintf('view_profile.view.platform.%s', $socialLink->platform)),
            ]); ?>
            <a href="{{ $socialLink->url }}"
               class="creator_social_link"
               target="_blank"
               rel="nofollow noopener noreferrer"
               title="{{ $socialLinkLabel }}"
               aria-label="{{ $socialLinkLabel }}">
                <i class="{{ $socialLink->getIconClass() }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
