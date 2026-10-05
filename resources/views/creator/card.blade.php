<?php

use App\Models\Season;
use App\Models\User;
use App\Service\Creator\Dtos\CreatorStats;

/**
 * A single creator tile, as the creator directory renders it. The featured-creators rail has its own flatter markup.
 *
 * The name's stretched link makes the whole tile open the profile, so the social links can sit inside the tile
 * without nesting one anchor in another.
 *
 * @var User        $creator     A user from UserRepository::buildListedCreatorsQuery(), which carries the stats columns,
 *                               with its social links loaded.
 * @var Season|null $statsSeason The season those stats were counted for.
 */

$creatorStats = CreatorStats::fromAttributes($creator->getAttributes(), $statsSeason);
?>
<div class="creator_card card h-100">
    <div class="card-body text-center">
        @if($creator->iconfile !== null)
            <img src="{{ $creator->iconfile->getURL() }}"
                 alt=""
                 class="creator_card_avatar mb-2"/>
        @else
            <div class="creator_card_initials mb-2" aria-hidden="true">
                {{ $creator->initials }}
            </div>
        @endif

        <a href="{{ route('profile.view', ['user' => $creator]) }}"
           class="creator_card_name stretched-link d-block text-decoration-none">
            {{ $creator->name }}
        </a>

        <div class="text-body-secondary small">
            {{ implode(' · ', $creatorStats->getSummaryParts()) }}
        </div>

        @include('creator.socials', ['socialLinks' => $creator->socialLinks, 'class' => 'creator_card_socials'])
    </div>
</div>
