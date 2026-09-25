<?php

use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiff;

/**
 * What the mapping version upgrade that produced the draft being edited changed about the route.
 *
 * @var MappingVersionUpgradeDiff $upgradeDiff
 */
?>

<h3 class="card-title">{{ __('view_common.modal.mappingversionupgradediff.title') }}</h3>

<p class="text-body-secondary">
    {{ __('view_common.modal.mappingversionupgradediff.description', [
        'oldVersion' => $upgradeDiff->oldMappingVersion->version,
        'newVersion' => $upgradeDiff->newMappingVersion->version,
    ]) }}
</p>

@if(!$upgradeDiff->hasRouteImpact())
    <p>
        <i class="fas fa-check text-success"></i>
        {{ __('view_common.modal.mappingversionupgradediff.no_route_impact') }}
    </p>
@endif

@if($upgradeDiff->emptiedPulls->isNotEmpty())
    <h5>
        <i class="fas fa-exclamation-triangle text-danger"></i>
        {{ __('view_common.modal.mappingversionupgradediff.emptied_pulls') }}
    </h5>
    <ul class="list-unstyled ms-3">
        @foreach($upgradeDiff->emptiedPulls as $pull)
            <li>@include('common.modal.mappingversionupgradediff.pull', ['pull' => $pull])</li>
        @endforeach
    </ul>
@endif

@if($upgradeDiff->removedPullEnemies->isNotEmpty())
    <h5>
        <i class="fas fa-minus-circle text-warning"></i>
        {{ __('view_common.modal.mappingversionupgradediff.removed_pull_enemies') }}
    </h5>
    <p class="text-body-secondary">
        {{ __('view_common.modal.mappingversionupgradediff.removed_pull_enemies_description') }}
    </p>
    <ul class="list-unstyled ms-3">
        @foreach($upgradeDiff->removedPullEnemies as $removedPullEnemy)
            {{-- @var MappingVersionUpgradeDiffEnemy $removedPullEnemy --}}
            <li>
                @include('common.modal.mappingversionupgradediff.pull', ['pull' => $removedPullEnemy->pull])
                {{ $removedPullEnemy->npc === null
                    ? __('view_common.modal.mappingversionupgradediff.unknown_npc')
                    : __($removedPullEnemy->npc->name) }}
                @if($removedPullEnemy->floor !== null)
                    <span class="text-body-secondary">({{ __($removedPullEnemy->floor->name) }})</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if($upgradeDiff->unkilledRequiredEnemies->isNotEmpty())
    <h5>
        <i class="fas fa-skull text-danger"></i>
        {{ __('view_common.modal.mappingversionupgradediff.unkilled_required_enemies') }}
    </h5>
    <p class="text-body-secondary">
        {{ __('view_common.modal.mappingversionupgradediff.unkilled_required_enemies_description') }}
    </p>
    <ul class="list-unstyled ms-3">
        @foreach($upgradeDiff->unkilledRequiredEnemies as $requiredEnemy)
            {{-- @var MappingVersionUpgradeDiffEnemy $requiredEnemy --}}
            <li>
                {{ $requiredEnemy->npc === null
                    ? __('view_common.modal.mappingversionupgradediff.unknown_npc')
                    : __($requiredEnemy->npc->name) }}
                @if($requiredEnemy->floor !== null)
                    <span class="text-body-secondary">({{ __($requiredEnemy->floor->name) }})</span>
                @endif
                @if($requiredEnemy->newlyRequired)
                    <span class="badge bg-info">{{ __('view_common.modal.mappingversionupgradediff.newly_required') }}</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if($upgradeDiff->movedPullEnemies->isNotEmpty())
    <h5>
        <i class="fas fa-arrows-alt text-info"></i>
        {{ __('view_common.modal.mappingversionupgradediff.moved_pull_enemies') }}
    </h5>
    <p class="text-body-secondary">
        {{ __('view_common.modal.mappingversionupgradediff.moved_pull_enemies_description') }}
    </p>
    <ul class="list-unstyled ms-3">
        @foreach($upgradeDiff->movedPullEnemies as $movedPullEnemy)
            {{-- @var MappingVersionUpgradeDiffMovedEnemy $movedPullEnemy --}}
            <li>
                @include('common.modal.mappingversionupgradediff.pull', ['pull' => $movedPullEnemy->pull])
                {{ $movedPullEnemy->npc === null
                    ? __('view_common.modal.mappingversionupgradediff.unknown_npc')
                    : __($movedPullEnemy->npc->name) }}
                <span class="text-body-secondary">
                    @if($movedPullEnemy->hasChangedFloor())
                        {{ __('view_common.modal.mappingversionupgradediff.moved_to_floor', [
                            'floor' => $movedPullEnemy->newFloor === null ? '?' : __($movedPullEnemy->newFloor->name),
                        ]) }}
                    @else
                        {{ __('view_common.modal.mappingversionupgradediff.moved_yards', [
                            'yards' => number_format($movedPullEnemy->distance, 0),
                        ]) }}
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
@endif

@if($upgradeDiff->hasEnemyForcesImpact())
    <h5>
        <i class="fas fa-users text-info"></i>
        {{ __('view_common.modal.mappingversionupgradediff.enemy_forces') }}
    </h5>
    <ul class="list-unstyled ms-3">
        @if($upgradeDiff->newEnemyForces !== null && $upgradeDiff->newEnemyForces !== $upgradeDiff->oldEnemyForces)
            <li>
                {{ __('view_common.modal.mappingversionupgradediff.enemy_forces_total', [
                    'old' => $upgradeDiff->oldEnemyForces,
                    'new' => $upgradeDiff->newEnemyForces,
                ]) }}
            </li>
        @endif
        @if($upgradeDiff->newEnemyForcesRequired !== $upgradeDiff->oldEnemyForcesRequired)
            <li>
                {{ __('view_common.modal.mappingversionupgradediff.enemy_forces_required', [
                    'old' => $upgradeDiff->oldEnemyForcesRequired,
                    'new' => $upgradeDiff->newEnemyForcesRequired,
                ]) }}
            </li>
        @endif
        @foreach($upgradeDiff->npcEnemyForcesChanges as $npcEnemyForcesChange)
            {{-- @var MappingVersionUpgradeDiffNpcEnemyForces $npcEnemyForcesChange --}}
            <li>
                {{ __('view_common.modal.mappingversionupgradediff.enemy_forces_npc', [
                    'npc' => $npcEnemyForcesChange->npc === null
                        ? __('view_common.modal.mappingversionupgradediff.unknown_npc')
                        : __($npcEnemyForcesChange->npc->name),
                    'old' => $npcEnemyForcesChange->oldEnemyForces ?? '-',
                    'new' => $npcEnemyForcesChange->newEnemyForces ?? '-',
                ]) }}
            </li>
        @endforeach
    </ul>
@endif

@if($upgradeDiff->dungeonStartLost || $upgradeDiff->lostRaidMarkerCount > 0)
    <h5>
        <i class="fas fa-map-pin text-warning"></i>
        {{ __('view_common.modal.mappingversionupgradediff.markers') }}
    </h5>
    <ul class="list-unstyled ms-3">
        @if($upgradeDiff->dungeonStartLost)
            <li>{{ __('view_common.modal.mappingversionupgradediff.dungeon_start_lost') }}</li>
        @endif
        @if($upgradeDiff->lostRaidMarkerCount > 0)
            <li>
                {{ trans_choice('view_common.modal.mappingversionupgradediff.raid_markers_lost', $upgradeDiff->lostRaidMarkerCount, [
                    'count' => $upgradeDiff->lostRaidMarkerCount,
                ]) }}
            </li>
        @endif
    </ul>
@endif

<h5>
    <i class="fas fa-map text-body-secondary"></i>
    {{ __('view_common.modal.mappingversionupgradediff.dungeon_wide') }}
</h5>
<ul class="list-unstyled ms-3">
    @if(!$upgradeDiff->hasMappingChanges())
        <li>{{ __('view_common.modal.mappingversionupgradediff.dungeon_wide_unchanged') }}</li>
    @else
        @if($upgradeDiff->addedEnemyCount > 0)
            <li>
                {{ trans_choice('view_common.modal.mappingversionupgradediff.enemies_added', $upgradeDiff->addedEnemyCount, [
                    'count' => $upgradeDiff->addedEnemyCount,
                ]) }}
            </li>
        @endif
        @if($upgradeDiff->removedEnemyCount > 0)
            <li>
                {{ trans_choice('view_common.modal.mappingversionupgradediff.enemies_removed', $upgradeDiff->removedEnemyCount, [
                    'count' => $upgradeDiff->removedEnemyCount,
                ]) }}
            </li>
        @endif
        @if($upgradeDiff->oldEnemyPatrolCount !== $upgradeDiff->newEnemyPatrolCount)
            <li>
                {{ __('view_common.modal.mappingversionupgradediff.enemy_patrols', [
                    'old' => $upgradeDiff->oldEnemyPatrolCount,
                    'new' => $upgradeDiff->newEnemyPatrolCount,
                ]) }}
            </li>
        @endif
    @endif
</ul>
