<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\MDTImport;
use App\Models\Metrics\Metric;
use App\Models\Metrics\MetricAggregation;
use App\Models\Path;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\RouteAttribute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteDeleteCleansUpRelationshipsTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * Every relation whose rows DungeonRoute's deleting hook removes. A BelongsToMany is listed when its pivot rows
     * are removed.
     */
    private const array CLEANED_UP_RELATIONS = [
        'affixGroups',
        'affixes',
        'arrows',
        'brushlines',
        'challengeModeRun',
        'classes',
        'dungeonRouteCollectionRoutes',
        'dungeonRouteThumbnailJobs',
        'dungeonRouteThumbnails',
        'enemyRaidMarkers',
        'favorites',
        'heroThumbnails',
        'killZones',
        'livesessions',
        'mdtImport',
        'metricAggregations',
        'metrics',
        'paths',
        'pinnedByUsers',
        'playerclasses',
        'playerraces',
        'playerspecializations',
        'races',
        'ratings',
        'routeMapIcons',
        'routeattributes',
        'routeattributesraw',
        'scheduledPublish',
        'specializations',
        'tags',
        'tagspersonal',
        'tagsteam',
        'thumbnails',
        'upgradeDraft',
    ];

    /**
     * Every relation whose rows deliberately survive the route's deletion, with the reason.
     *
     * @var array<string, string>
     */
    private const array KEPT_RELATIONS = [
        'mapicons'    => 'Widens itself to the team wide map icons, which belong to the team - the icons the route owns are routeMapIcons',
        'pageviews'   => 'Traffic history - page-views:prune rolls them up into page_view_counts and ages them out after the retention period',
        'userreports' => 'Moderation record - the admin report list keeps showing a report whose route is gone until an admin handles it',
    ];

    #[Test]
    public function relations_givenDungeonRouteModel_areEachEitherCleanedUpOrDeliberatelyKept(): void
    {
        // Arrange
        $classifiedRelations = array_merge(self::CLEANED_UP_RELATIONS, array_keys(self::KEPT_RELATIONS));

        // Act
        $relations = $this->getDungeonRouteRelationNames();

        // Assert
        $this->assertSame(
            [],
            array_values(array_intersect(self::CLEANED_UP_RELATIONS, array_keys(self::KEPT_RELATIONS))),
            'A relation cannot be both cleaned up and kept',
        );
        $this->assertSame(
            [],
            array_values(array_diff($relations, $classifiedRelations)),
            'These DungeonRoute relations are neither cleaned up on delete nor listed as deliberately kept - delete their rows in DungeonRoute\'s deleting hook and add them to CLEANED_UP_RELATIONS, or add them to KEPT_RELATIONS with the reason',
        );
        $this->assertSame(
            [],
            array_values(array_diff($classifiedRelations, $relations)),
            'These listed relations no longer exist on DungeonRoute',
        );
    }

    #[Test]
    public function delete_givenRouteWithARowInEveryCleanedUpRelation_leavesNoRowBehind(): void
    {
        // Arrange
        $owner      = null;
        $route      = null;
        $collection = null;
        $fileIds    = [];

        try {
            [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false);
            $floor                      = $dungeon->floors()->where('facade', 0)->firstOrFail();

            $owner = User::factory()->create();
            $route = DungeonRoute::factory()->create([
                'author_id'          => $owner->id,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
                'expires_at'         => null,
            ]);
            $collection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);

            $fileIds = $this->createRowInEveryCleanedUpRelation($route, $owner, $floor->id, $collection);

            foreach (self::CLEANED_UP_RELATIONS as $relation) {
                $this->assertTrue(
                    $this->relationHasRows($route, $relation),
                    sprintf('The fixture must give relation %s a row, or its cleanup is not tested', $relation),
                );
            }

            // Act
            $route->delete();

            // Assert
            $this->assertNull(DungeonRoute::find($route->id), 'The route itself should be deleted');
            foreach (self::CLEANED_UP_RELATIONS as $relation) {
                $this->assertFalse(
                    $this->relationHasRows($route, $relation),
                    sprintf('Deleting the route left rows behind in relation %s', $relation),
                );
            }
            $this->assertSame(
                0,
                File::query()->whereIn('id', $fileIds)->count(),
                'The files of the route\'s thumbnails and thumbnail jobs should be deleted',
            );
        } finally {
            if ($route !== null) {
                $this->deleteLeftoverRows($route);
                DungeonRoute::query()->whereKey($route->id)->delete();
            }
            File::query()->whereIn('id', $fileIds)->delete();
            $collection?->delete();
            $owner?->delete();
        }
    }

    /**
     * @return array<int, string>
     */
    private function getDungeonRouteRelationNames(): array
    {
        $relations = [];

        foreach ((new ReflectionClass(DungeonRoute::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getDeclaringClass()->getName() !== DungeonRoute::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!$returnType instanceof ReflectionNamedType || $returnType->isBuiltin()) {
                continue;
            }

            $returnClass = $returnType->getName();
            if (is_a($returnClass, Relation::class, true) && !is_a($returnClass, BelongsTo::class, true)) {
                $relations[] = $method->getName();
            }
        }

        return $relations;
    }

    /**
     * @return array<int, int> The ids of the files the thumbnails and thumbnail job point to.
     */
    private function createRowInEveryCleanedUpRelation(
        DungeonRoute           $route,
        User                   $owner,
        int                    $floorId,
        DungeonRouteCollection $collection,
    ): array {
        $fileIds = [];

        Brushline::create(['dungeon_route_id' => $route->id, 'floor_id' => $floorId, 'polyline_id' => -1]);
        Path::create(['dungeon_route_id' => $route->id, 'floor_id' => $floorId, 'polyline_id' => -1]);
        Arrow::create(['dungeon_route_id' => $route->id, 'floor_id' => $floorId, 'polyline_id' => -1]);
        KillZone::create(['dungeon_route_id' => $route->id, 'floor_id' => null, 'color' => '#ff0000', 'index' => 1]);
        MapIcon::factory()->create([
            'dungeon_route_id'   => $route->id,
            'mapping_version_id' => null,
            'floor_id'           => $floorId,
            'team_id'            => null,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
        ]);
        DungeonRouteEnemyRaidMarker::create([
            'dungeon_route_id' => $route->id,
            'raid_marker_id'   => RaidMarker::ALL['skull'],
            'npc_id'           => 12345,
            'mdt_id'           => 1,
            'enemy_id'         => 99999,
        ]);

        DungeonRouteAffixGroup::create(['dungeon_route_id' => $route->id, 'affix_group_id' => AffixGroup::query()->value('id')]);
        DungeonRouteAttribute::insert(['dungeon_route_id' => $route->id, 'route_attribute_id' => RouteAttribute::query()->value('id')]);
        DungeonRoutePlayerClass::create(['dungeon_route_id' => $route->id, 'character_class_id' => CharacterClass::query()->value('id')]);
        DungeonRoutePlayerRace::insert(['dungeon_route_id' => $route->id, 'character_race_id' => CharacterRace::query()->value('id')]);
        DungeonRoutePlayerSpecialization::create([
            'dungeon_route_id'                  => $route->id,
            'character_class_specialization_id' => CharacterClassSpecialization::query()->value('id'),
        ]);

        ChallengeModeRun::factory()->create(['dungeon_id' => $route->dungeon_id, 'dungeon_route_id' => $route->id]);

        DungeonRouteRating::create(['dungeon_route_id' => $route->id, 'user_id' => $owner->id, 'rating' => 5]);
        DungeonRouteFavorite::create(['dungeon_route_id' => $route->id, 'user_id' => $owner->id]);
        UserPinnedDungeonRoute::create(['dungeon_route_id' => $route->id, 'user_id' => $owner->id, 'order' => 0]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $collection->id,
            'dungeon_route_id'            => $route->id,
            'order'                       => 0,
        ]);
        LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
        MDTImport::create(['dungeon_route_id' => $route->id, 'import_string' => 'route-delete-test']);
        DungeonRouteScheduledPublish::create([
            'dungeon_route_id' => $route->id,
            'published_state'  => PublishedState::WORLD,
            'publish_at'       => now()->addDay(),
        ]);

        foreach ([TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM] as $tagCategoryName) {
            Tag::create([
                'tag_category_id' => TagCategory::ALL[$tagCategoryName],
                'model_id'        => $route->id,
                'model_class'     => DungeonRoute::class,
                'user_id'         => $owner->id,
                'name'            => 'route-delete-test',
                'color'           => '#ff0000',
            ]);
        }

        foreach ([Metric::class, MetricAggregation::class] as $metricClass) {
            $metricClass::create([
                'model_id'    => $route->id,
                'model_class' => DungeonRoute::class,
                'category'    => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
                'tag'         => Metric::TAG_MDT_COPY_VIEW,
                'value'       => 1,
            ]);
        }

        foreach ([DungeonRouteThumbnailVariant::Standard, DungeonRouteThumbnailVariant::Hero] as $variant) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $route->id,
                'floor_id'         => $floorId,
                'variant'          => $variant,
            ]);
            $file = $this->createFile($thumbnail->id, DungeonRouteThumbnail::class);
            $thumbnail->update(['file_id' => $file->id]);
            $fileIds[] = $file->id;
        }

        $thumbnailJob = DungeonRouteThumbnailJob::create([
            'dungeon_route_id' => $route->id,
            'floor_id'         => $floorId,
            'status'           => DungeonRouteThumbnailJob::STATUS_COMPLETED,
        ]);
        $file = $this->createFile($thumbnailJob->id, DungeonRouteThumbnailJob::class);
        $thumbnailJob->update(['file_id' => $file->id]);
        $fileIds[] = $file->id;

        DungeonRoute::factory()->create([
            'author_id'                   => $owner->id,
            'dungeon_id'                  => $route->dungeon_id,
            'mapping_version_id'          => $route->mapping_version_id,
            'upgrade_of_dungeon_route_id' => $route->id,
            'expires_at'                  => null,
        ]);

        return $fileIds;
    }

    private function createFile(int $modelId, string $modelClass): File
    {
        return File::create([
            'model_id'    => $modelId,
            'model_class' => $modelClass,
            'disk'        => config('filesystems.default'),
            'path'        => sprintf('thumbnails/route_delete_test_%s.jpg', uniqid()),
        ]);
    }

    private function relationHasRows(DungeonRoute $route, string $relation): bool
    {
        // challengeModeRun() only resolves with the route switched to the combatlog connection, see its docblock
        if ($relation === 'challengeModeRun') {
            $hasRows = $route->setConnection('combatlog')->challengeModeRun()->exists();
            $route->setConnection(null);

            return $hasRows;
        }

        return $route->{$relation}()->exists();
    }

    private function deleteLeftoverRows(DungeonRoute $route): void
    {
        foreach (self::CLEANED_UP_RELATIONS as $relation) {
            if ($relation === 'challengeModeRun') {
                ChallengeModeRun::query()->where('dungeon_route_id', $route->id)->delete();

                continue;
            }

            $query = $route->{$relation}();
            if ($query instanceof BelongsToMany) {
                continue;
            }

            $query->delete();
        }
    }
}
