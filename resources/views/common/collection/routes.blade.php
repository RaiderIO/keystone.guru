<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * The routes of a collection: one ordered slot per dungeon for a season set, one flat list for a free-form
 * collection. Routes are added through the route picker drawer. An existing collection saves every change at
 * once; a new one posts its routes with the details form that creates it.
 *
 * @var DungeonRouteCollection|null                   $dungeonRouteCollection Null for a collection being created.
 * @var Collection<int, DungeonRouteCollectionGroup>  $editSections
 * @var bool                                          $hasOwnDungeonRoutes
 * @var bool                                          $mayAddDungeonRoutes    Only the owner can pick from their own routes.
 * @var GameVersion                                   $selectedGameVersion
 * @var Season|null                                   $selectedSeason
 * @var string|null                                   $formId                 The form a new collection's routes are posted with.
 */

$dungeonRouteCollection ??= null;
$formId                 ??= null;
$isNew                  = $dungeonRouteCollection === null;

// A route of a dungeon outside the season's pool has no section of its own, so the sections are not the whole
// collection - an existing collection counts its own routes instead
$collectionDungeonRoutes = $isNew
    ? $editSections->flatMap(static fn(DungeonRouteCollectionGroup $editSection): Collection => $editSection->dungeonRoutes)
        ->unique('id')
        ->values()
    : $dungeonRouteCollection->dungeonRoutes;
$collectionDungeonRouteCount = $collectionDungeonRoutes->count();
/** @var array<string, int> $collectionDungeonIds The dungeon of every route of the collection, by public key. */
$collectionDungeonIds = $collectionDungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
    $dungeonRoute->public_key => $dungeonRoute->dungeon_id,
])->all();
$pickerId                    = 'collection_route_picker';
// A route may only be added to a dungeon the collection actually covers; a free-form collection covers every dungeon
$poolDungeonIds = $selectedSeason?->dungeons->pluck('id')->all() ?? [];

// A route's enemy forces against what its mapping version requires, flagged when the route falls short. Dungeons
// that require none (most classic ones) get no detail.
$enemyForcesDetails = $collectionDungeonRoutes
    ->filter(static fn(DungeonRoute $dungeonRoute): bool => $dungeonRoute->mappingVersion?->enemy_forces_required > 0)
    ->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
        $dungeonRoute->public_key => [
            'text'      => sprintf('%d / %d', $dungeonRoute->enemy_forces, $dungeonRoute->mappingVersion->enemy_forces_required),
            'isWarning' => $dungeonRoute->enemy_forces < $dungeonRoute->mappingVersion->enemy_forces_required,
        ],
    ])
    ->all();

$sections = [];
foreach ($editSections as $editSection) {
    $sectionId = $editSection->dungeon !== null
        ? sprintf('dungeon_routes_%d', $editSection->dungeon->id)
        : 'dungeon_routes';

    $sections[] = [
        'id'        => $sectionId,
        'inlineId'  => sprintf('%s_inline', $sectionId),
        'dungeonId' => $editSection->dungeon?->id,
        'canAdd'    => $editSection->dungeon === null || in_array($editSection->dungeon->id, $poolDungeonIds, true),
        'section'   => $editSection,
    ];
}

/**
 * One section's ordered list. Rendered inside a dungeon's slot card, or on its own for the flat list.
 *
 * @return array<string, mixed>
 */
$orderedSelectOptions = static function (array $section) use (
    $enemyForcesDetails,
    $formId,
    $mayAddDungeonRoutes,
    $collectionDungeonRouteCount,
): array {
    /** @var DungeonRouteCollectionGroup $editSection */
    $editSection = $section['section'];
    $dungeonName = $editSection->dungeon !== null ? __($editSection->dungeon->name) : null;

    return [
        'id'                => $section['id'],
        'name'              => 'dungeon_routes',
        'formId'            => $formId,
        'ajax'              => true,
        'showCount'         => $dungeonName !== null,
        'showCountMax'      => false,
        'fullCount'         => $collectionDungeonRouteCount,
        'showAdd'           => $mayAddDungeonRoutes && $section['canAdd'],
        'label'             => $dungeonName ?? __('view_common.collection.details.dungeon_routes'),
        'labelClass'        => 'form-label fw-bold',
        'addLabel'          => $dungeonName !== null
            ? __('view_common.collection.routes.add_route_for', ['dungeon' => $dungeonName])
            : __('view_common.collection.routes.add_routes'),
        'options'           => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
            $dungeonRoute->public_key => $dungeonName !== null
                ? $dungeonRoute->title
                : sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon->name)),
        ])->all(),
        'optionDetails'     => $enemyForcesDetails,
        'detailWarningText' => __('view_common.collection.details.enemy_forces_short'),
        'selectedIds'       => $editSection->dungeonRoutes->pluck('public_key')->all(),
        'max'               => DungeonRouteCollection::MAX_ROUTES,
        'itemMax'           => $dungeonName !== null ? DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON : null,
        'emptyText'         => $dungeonName !== null
            ? __('view_common.collection.details.dungeon_routes_slot_empty', ['dungeon' => $dungeonName])
            : __('view_common.collection.details.dungeon_routes_empty'),
    ];
};

$slotSections = array_values(array_filter($sections, static fn(array $section): bool => $section['dungeonId'] !== null));
$flatSections = array_values(array_filter($sections, static fn(array $section): bool => $section['dungeonId'] === null));

$inlineId      = 'collection_routes_inline';
$inlineOptions = [
    'dungeonRoutePickerInlineId' => $mayAddDungeonRoutes ? $pickerId : null,
    'dungeonRoutePickerSelector' => sprintf('#%s', $pickerId),
    'countSelector'              => '#collection_routes_count',
    'sections'                   => array_map(static fn(array $section): array => [
        'inlineId'          => $section['inlineId'],
        'rootSelector'      => sprintf('#%s', $section['id']),
        'addButtonSelector' => sprintf('#%s_add_button', $section['id']),
        'dungeonId'         => $section['dungeonId'],
        'canAdd'            => $section['canAdd'],
        'withDungeonName'   => $section['dungeonId'] === null,
    ], $sections),
    // A collection that does not exist yet has nothing to save to: its routes are posted with the form that creates it
    'deleteUrl'                  => $isNew ? null : route('ajax.collection.routes.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'orderUrl'                   => $isNew ? null : route('ajax.collection.routes.order', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'storeUrl'                   => $isNew ? null : route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]),
    'max'                        => DungeonRouteCollection::MAX_ROUTES,
    'dungeonIds'                 => $collectionDungeonIds,
    'maxPerDungeon'              => DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON,
];
?>
{{-- The data-inline-* attributes let a script activate this section again after swapping it into the page --}}
<section id="collection_routes" class="mb-4" aria-labelledby="collection_routes_heading"
         data-inline-id="{{ $inlineId }}" data-inline-path="common/collection/routes"
         data-inline-options="{{ json_encode($inlineOptions) }}">
    <div class="d-flex align-items-baseline mb-2">
        <h2 id="collection_routes_heading" class="h4 mb-0">{{ __('view_common.collection.routes.heading') }}</h2>
        <span id="collection_routes_count" class="text-body-secondary ms-auto">
            {{ __('view_common.collection.routes.count', ['count' => $collectionDungeonRouteCount, 'max' => DungeonRouteCollection::MAX_ROUTES]) }}
        </span>
    </div>
    <p class="form-text text-body-secondary mt-0">
        {{ __($isNew ? 'view_common.collection.routes.help_new' : 'view_common.collection.routes.help') }}
        @if(!$mayAddDungeonRoutes)
            {{ __('view_common.collection.routes.owner_only') }}
        @endif
    </p>

    <div id="collection_routes_loading" class="text-body-secondary mb-2" role="status" hidden>
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> {{ __('view_common.collection.details.dungeon_routes_loading') }}
    </div>
    <div id="collection_routes_error" class="text-danger small mb-2" role="alert" hidden>
        {{ __('view_common.collection.details.dungeon_routes_load_failed') }}
    </div>

    @if(!$hasOwnDungeonRoutes)
        <p class="text-body-secondary">
            {{ __('view_common.collection.details.dungeon_routes_none') }}
        </p>
    @endif

    @if(!empty($slotSections))
        <div class="row row-cols-1 row-cols-lg-2 g-3 mb-3">
            @foreach($slotSections as $section)
                <div class="col collection_routes_section" data-section-id="{{ $section['id'] }}">
                    <div class="collection_slot h-100"
                         style="background-image: url('{{ $section['section']->dungeon->getImageUrl() }}')">
                        <div class="collection_slot_scrim h-100 p-3">
                            @include('common.forms.orderedselect', $orderedSelectOptions($section))
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @foreach($flatSections as $section)
        <div class="mb-3 collection_routes_section" data-section-id="{{ $section['id'] }}">
            @include('common.forms.orderedselect', $orderedSelectOptions($section))
        </div>
    @endforeach

    @foreach(collect($errors->get('dungeon_routes.*'))->flatten()->unique() as $dungeonRoutesError)
        <div class="invalid-feedback d-block" role="alert">
            <strong>{{ $dungeonRoutesError }}</strong>
        </div>
    @endforeach

    @if($mayAddDungeonRoutes)
        @include('common.dungeonroute.picker', [
            'id' => $pickerId,
            'title' => $isNew
                ? __('view_common.collection.routes.picker_title_new')
                : __('view_common.collection.routes.picker_title', ['name' => $dungeonRouteCollection->name]),
            'sourceScope' => 'mine',
            'lockedGameVersion' => $selectedGameVersion,
            'lockedSeason' => $selectedSeason,
            'preselectedDungeon' => null,
            'existingPublicKeys' => $collectionDungeonRoutes->pluck('public_key')->all(),
            'max' => DungeonRouteCollection::MAX_ROUTES,
            'maxPerDungeon' => DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON,
            'existingDungeonIds' => $collectionDungeonIds,
            // A new collection has nothing to post to yet: its routes are added to the form and saved with it
            'addUrl' => $isNew ? null : route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            'addFieldName' => 'dungeon_routes',
            'openButtonSelector' => null,
        ])
    @endif
</section>

@include('common.general.inline', ['path' => 'common/collection/routes', 'id' => $inlineId, 'options' => $inlineOptions])
