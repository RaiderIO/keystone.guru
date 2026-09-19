<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * The routes of an existing collection, saved immediately: one ordered list per pool dungeon for a season set, one
 * flat list for a free-form collection, and a trailing list of routes that do not match. Routes are added through
 * the route picker drawer.
 *
 * @var DungeonRouteCollection                         $dungeonRouteCollection With its routes, season and dungeons loaded.
 * @var Collection<int, DungeonRouteCollectionGroup>   $editSections
 * @var bool                                           $hasOwnDungeonRoutes
 * @var bool                                           $mayAddDungeonRoutes    Only the owner can pick from their own routes.
 */

$memberDungeonRoutes = $dungeonRouteCollection->dungeonRoutes;
$memberCount         = $memberDungeonRoutes->count();
$gameVersion         = $dungeonRouteCollection->gameVersion ?? GameVersion::getDefaultGameVersion();
$pickerId            = 'collection_route_picker';

$sections = [];
foreach ($editSections as $editSection) {
    if (!$editSection->matchesCollection) {
        $sectionId = 'dungeon_routes_foreign';
    } elseif ($editSection->dungeon !== null) {
        $sectionId = sprintf('dungeon_routes_%d', $editSection->dungeon->id);
    } else {
        $sectionId = 'dungeon_routes';
    }

    $sections[] = [
        'id'        => $sectionId,
        'inlineId'  => sprintf('%s_inline', $sectionId),
        'dungeonId' => $editSection->matchesCollection ? $editSection->dungeon?->id : null,
        'isForeign' => !$editSection->matchesCollection,
        'section'   => $editSection,
    ];
}
?>
<section id="collection_routes" class="mb-4" aria-labelledby="collection_routes_heading">
    <div class="d-flex align-items-baseline mb-2">
        <h2 id="collection_routes_heading" class="h4 mb-0">{{ __('view_common.collection.routes.heading') }}</h2>
        <span id="collection_routes_count" class="text-body-secondary ms-auto">
            {{ __('view_common.collection.routes.count', ['count' => $memberCount, 'max' => DungeonRouteCollection::MAX_ROUTES]) }}
        </span>
    </div>
    <p class="form-text text-body-secondary mt-0">
        {{ __('view_common.collection.routes.help') }}
        @if(!$mayAddDungeonRoutes)
            {{ __('view_common.collection.routes.owner_only') }}
        @endif
    </p>

    @if(!$hasOwnDungeonRoutes)
        <p class="text-body-secondary">
            {{ __('view_common.collection.details.dungeon_routes_none') }}
        </p>
    @endif

    @foreach($sections as $section)
        <?php
        /** @var DungeonRouteCollectionGroup $editSection */
        $editSection = $section['section'];
        $dungeonName = $editSection->dungeon !== null ? __($editSection->dungeon->name) : null;
        ?>
        <div class="mb-3 collection_routes_section" data-section-id="{{ $section['id'] }}">
            @include('common.forms.orderedselect', [
                'id' => $section['id'],
                'name' => 'dungeon_routes',
                'ajax' => true,
                'showCount' => false,
                'fullCount' => $memberCount,
                'showAdd' => $mayAddDungeonRoutes && !$section['isForeign'],
                'label' => match (true) {
                    $section['isForeign'] => __('view_common.collection.details.dungeon_routes_foreign'),
                    $dungeonName !== null => $dungeonName,
                    default => __('view_common.collection.details.dungeon_routes'),
                },
                'labelClass' => 'form-label fw-bold',
                'addLabel' => $dungeonName !== null
                    ? __('view_common.collection.routes.add_route_for', ['dungeon' => $dungeonName])
                    : __('view_common.collection.routes.add_routes'),
                'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                    $dungeonRoute->id => $dungeonName !== null
                        ? $dungeonRoute->title
                        : sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon?->name ?? '')),
                ])->all(),
                'selectedIds' => $editSection->dungeonRoutes->pluck('id')->all(),
                'max' => DungeonRouteCollection::MAX_ROUTES,
                'help' => $section['isForeign'] ? __('view_common.collection.details.dungeon_routes_foreign_help') : null,
                'emptyText' => $dungeonName !== null
                    ? __('view_common.collection.details.dungeon_routes_slot_empty', ['dungeon' => $dungeonName])
                    : __('view_common.collection.details.dungeon_routes_empty'),
            ])
        </div>
    @endforeach
</section>

@if($mayAddDungeonRoutes)
    @include('common.dungeonroute.picker', [
        'id' => $pickerId,
        'title' => __('view_common.collection.routes.picker_title', ['name' => $dungeonRouteCollection->name]),
        'sourceScope' => 'mine',
        'lockedGameVersion' => $gameVersion,
        'lockedSeason' => $dungeonRouteCollection->season,
        'preselectedDungeon' => null,
        'existingPublicKeys' => $memberDungeonRoutes->pluck('public_key')->all(),
        'max' => DungeonRouteCollection::MAX_ROUTES,
        'addUrl' => route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]),
        'addFieldName' => 'dungeon_routes',
        'openButtonSelector' => null,
    ])
@endif

@include('common.general.inline', ['path' => 'common/collection/routes', 'options' => [
    'pickerInlineId'    => $mayAddDungeonRoutes ? $pickerId : null,
    'pickerSelector'    => sprintf('#%s', $pickerId),
    'countSelector'     => '#collection_routes_count',
    'sections'          => array_map(static fn(array $section): array => [
        'inlineId'          => $section['inlineId'],
        'rootSelector'      => sprintf('#%s', $section['id']),
        'addButtonSelector' => sprintf('#%s_add_button', $section['id']),
        'dungeonId'         => $section['dungeonId'],
        'isForeign'         => $section['isForeign'],
        'withDungeonName'   => $section['dungeonId'] === null,
    ], $sections),
    'dungeonRoutes'     => $memberDungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
        $dungeonRoute->id => $dungeonRoute->public_key,
    ])->all(),
    'deleteUrl'         => route('ajax.collection.routes.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'orderUrl'          => route('ajax.collection.routes.order', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'storeUrl'          => route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'max'               => DungeonRouteCollection::MAX_ROUTES,
    'countText'         => __('view_common.collection.routes.count'),
    'addedOneText'      => __('view_common.collection.routes.added_one'),
    'addedManyText'     => __('view_common.collection.routes.added_many'),
    'removedText'       => __('view_common.collection.routes.removed'),
    'undoText'          => __('view_common.collection.routes.undo'),
    'undoneText'        => __('view_common.collection.routes.undone'),
    'saveFailedText'    => __('view_common.collection.routes.save_failed'),
]])
