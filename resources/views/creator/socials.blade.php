<?php

use App\Models\User;
use App\Models\UserSocialLink;
use Illuminate\Support\Collection;

/**
 * A creator's social links as a row of round icon links, on the directory card and the profile hero.
 *
 * @var User                            $user        Whose links these are.
 * @var Collection<int, UserSocialLink> $socialLinks
 * @var string                          $class       Where the row sits: creator_card_socials or creator_hero_socials.
 */
?>
@if($socialLinks->isNotEmpty())
    <div class="creator_socials {{ $class }}">
        @foreach($socialLinks as $socialLink)
            <?php
            $socialLinkHref     = $socialLink->getHref();
            $socialLinkPlatform = __(sprintf('view_profile.view.platform.%s', $socialLink->platform));
            ?>
            @if($socialLinkHref === null)
                {{-- A username without a profile link (Discord): shown, never linked --}}
                <?php $socialLinkLabel = __('view_profile.view.social_username', [
                    'platform' => $socialLinkPlatform,
                    'username' => $socialLink->url,
                ]); ?>
                <span class="creator_social_link"
                      tabindex="0"
                      role="img"
                      data-bs-toggle="tooltip"
                      title="{{ $socialLinkLabel }}"
                      aria-label="{{ $socialLinkLabel }}">
                    <i class="{{ $socialLink->getIconClass() }}" aria-hidden="true"></i>
                </span>
            @elseif($socialLink->needsLeavingPage())
                <?php $socialLinkLabel = __('view_profile.view.social_link_host', [
                    'platform' => $socialLinkPlatform,
                    'host'     => $socialLink->getHost(),
                ]); ?>
                <a href="{{ route('profile.sociallink.leave', ['user' => $user, 'socialLink' => $socialLink]) }}"
                   class="creator_social_link"
                   target="_blank"
                   rel="nofollow"
                   data-bs-toggle="tooltip"
                   title="{{ $socialLinkLabel }}"
                   aria-label="{{ $socialLinkLabel }}">
                    <i class="{{ $socialLink->getIconClass() }}" aria-hidden="true"></i>
                </a>
            @else
                <?php $socialLinkLabel = __('view_profile.view.social_link', ['platform' => $socialLinkPlatform]); ?>
                <a href="{{ $socialLinkHref }}"
                   class="creator_social_link"
                   target="_blank"
                   rel="nofollow noopener noreferrer"
                   title="{{ $socialLinkLabel }}"
                   aria-label="{{ $socialLinkLabel }}">
                    <i class="{{ $socialLink->getIconClass() }}" aria-hidden="true"></i>
                </a>
            @endif
        @endforeach
    </div>
@endif
