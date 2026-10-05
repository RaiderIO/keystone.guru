<?php

use App\Service\Creator\Dtos\CreatorStats;

/**
 * The season's dungeons as one fixed row of images: in colour where the creator has season routes, greyed out where
 * not. Every row lists the same dungeons in the same order, so a dungeon sits in the same place on every card.
 *
 * The list's aria-label stands in for the images when the row sits inside a link, keeping the link's name short.
 *
 * @var CreatorStats $creatorStats
 */

$seasonDungeonCoverage = $creatorStats->getSeasonDungeonCoverage();
$coveredCount          = $seasonDungeonCoverage->where('covered', true)->count();
$totalCount            = $seasonDungeonCoverage->count();
?>
@if($totalCount > 0)
    <div class="creator_coverage">
        <ul class="creator_coverage_dungeons"
            aria-label="{{ __('view_creator.stats.coverage', ['count' => $coveredCount, 'total' => $totalCount]) }}">
            @foreach($seasonDungeonCoverage as $dungeonCoverage)
                <?php
                $dungeonCoverageLabel = __(
                    $dungeonCoverage['covered'] ? 'view_creator.stats.coverage_dungeon_covered' : 'view_creator.stats.coverage_dungeon_missing',
                    ['dungeon' => __($dungeonCoverage['dungeon']->name)],
                );
                ?>
                <li>
                    <img src="{{ $dungeonCoverage['dungeon']->getImageUrl() }}"
                         class="creator_coverage_dungeon {{ $dungeonCoverage['covered'] ? 'covered' : 'missing' }}"
                         alt="{{ $dungeonCoverageLabel }}"
                         title="{{ $dungeonCoverageLabel }}"
                         loading="lazy"/>
                </li>
            @endforeach
        </ul>
        <span class="creator_coverage_count text-body-secondary small" aria-hidden="true">
            {{ __('view_creator.stats.coverage_count', ['count' => $coveredCount, 'total' => $totalCount]) }}
        </span>
    </div>
@endif
