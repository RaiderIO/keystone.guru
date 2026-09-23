<?php

use App\Models\Season;
use App\Models\User;
use App\Service\Creator\Dtos\CreatorStats;

/**
 * A single creator tile, as the creator directory renders it. The featured-creators rail has its own flatter markup.
 *
 * @var User        $creator     A user from UserRepository::buildListedCreatorsQuery(), which carries the stats columns.
 * @var Season|null $statsSeason The season those stats were counted for.
 */

$creatorStats = CreatorStats::fromAttributes($creator->getAttributes(), $statsSeason);
?>
<a href="{{ route('profile.view', ['user' => $creator]) }}" class="creator_card card h-100 text-decoration-none">
    <div class="card-body text-center">
        @if($creator->iconfile !== null)
            <img src="{{ $creator->iconfile->getURL() }}"
                 alt="{{ $creator->name }}"
                 class="creator_card_avatar mb-2"/>
        @else
            <div class="creator_card_initials mb-2" aria-hidden="true">
                {{ $creator->initials }}
            </div>
        @endif

        <div class="creator_card_name">
            {{ $creator->name }}
        </div>

        <div class="text-body-secondary small">
            {{ implode(' · ', $creatorStats->getSummaryParts()) }}
        </div>
    </div>
</a>
