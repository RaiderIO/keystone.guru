<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Logic\MDT\Exception\ImportError;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteDraftSource;
use App\Models\DungeonStart;
use App\Models\GameVersion\GameVersion;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\MDTImport;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Repositories\Interfaces\DungeonStartRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftService;
use App\Service\DungeonRoute\Exceptions\PendingUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\StaleUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use App\Service\DungeonRoute\Logging\DungeonRouteUpgradeDraftServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\MDTImportStringService;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\App\Service\MDT\MDTImportStringServiceTestBase;

#[Group('UsesLua')]
#[Group('DungeonRoute')]
#[Group('DungeonRouteUpgradeDraftServiceMdtImport')]
final class DungeonRouteUpgradeDraftServiceMdtImportTest extends MDTImportStringServiceTestBase
{
    /**
     * Every model created by a test, torn down in order.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    #[Test]
    public function createDraftFromMdtString_givenMatchingString_createsDraftWithStringContentAndOriginalMetadata(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $this->createBrushlineForRoute($original);
            $originalAffixGroupIds = $original->affixGroups()->pluck('affix_group_id')->sort()->values()->all();
            $warnings              = collect();

            // Act
            $draft           = $this->buildService()->createDraftFromMdtString($original, $mdtString, $warnings);
            $this->cleanup[] = $draft;

            // Assert
            $this->assertSame($original->id, $draft->upgrade_of_dungeon_route_id);
            $this->assertSame(DungeonRouteDraftSource::MdtImport, $draft->draft_source);
            $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $draft->published_state_id);
            $this->assertSame($original->author_id, $draft->author_id);
            $this->assertSame($original->title, $draft->title);
            $this->assertSame($original->description, $draft->description);
            $this->assertSame($original->level_min, $draft->level_min);
            $this->assertSame($original->level_max, $draft->level_max);
            $this->assertSame($original->difficulty, $draft->difficulty);
            $this->assertSame($original->pull_gradient, $draft->pull_gradient);
            $this->assertEquals(
                $originalAffixGroupIds,
                $draft->affixGroups()->pluck('affix_group_id')->sort()->values()->all(),
                'The affixes of the original must win over the string\'s',
            );
            $this->assertSame(2, $draft->killZones()->count(), 'The draft holds the string\'s pulls');
            $this->assertSame(0, $draft->brushlines()->count(), 'The draft does not carry the original\'s content');
            $this->assertSame(1, MDTImport::query()->where('dungeon_route_id', $draft->id)->count());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenMdtImportDraft_replacesContentKeepsMetadataAndRepointsMdtImport(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $this->createBrushlineForRoute($original);
            $previousMdtImport = MDTImport::create([
                'dungeon_route_id' => $original->id,
                'import_string'    => 'previous',
            ]);
            $originalAffixGroupIds = $original->affixGroups()->pluck('affix_group_id')->sort()->values()->all();
            $service               = $this->buildService();
            $draft                 = $service->createDraftFromMdtString($original, $mdtString, collect());
            $draftMdtImport        = MDTImport::query()->where('dungeon_route_id', $draft->id)->firstOrFail();

            // Act
            $applied = $service->apply($draft);

            // Assert
            $this->assertSame($original->id, $applied->id);
            $this->assertSame($original->public_key, $applied->public_key);
            $this->assertSame($original->title, $applied->title);
            $this->assertSame($original->description, $applied->description);
            $this->assertSame(2, $applied->killZones()->count());
            $this->assertSame(0, $applied->brushlines()->count());
            $this->assertEquals(
                $originalAffixGroupIds,
                $applied->affixGroups()->pluck('affix_group_id')->sort()->values()->all(),
            );
            $this->assertNull(DungeonRoute::find($draft->id), 'The draft is gone once applied');
            $this->assertNull(MDTImport::find($previousMdtImport->id), 'The original\'s previous import is replaced');
            $this->assertSame($original->id, $draftMdtImport->fresh()?->dungeon_route_id, 'The import follows the content');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenMdtImportDraftOfRouteWithChosenStart_keepsChosenStart(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $dungeonStart         = $this->createDungeonStart($source->mappingVersion, 'Chosen start');
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id, [
                'dungeon_start_id' => $dungeonStart->id,
            ]);
            $service = $this->buildService();
            $draft   = $service->createDraftFromMdtString($original, $mdtString, collect());

            // Act
            $applied = $service->apply($draft);

            // Assert
            $this->assertSame($dungeonStart->id, $draft->dungeon_start_id, 'The draft carries the original\'s start');
            $this->assertSame($dungeonStart->id, $applied->dungeon_start_id);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenSiteAheadOfMdtAndChosenStart_remapsStartOntoUpgradedMappingVersion(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $dungeonStart         = $this->createDungeonStart($source->mappingVersion, 'Chosen start');
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id, [
                'dungeon_start_id' => $dungeonStart->id,
            ]);
            $siteMappingVersion = $this->createNewerMappingVersion($source->dungeon, $source->mappingVersion, mdtChangesPending: true);
            array_unshift($this->cleanup, $siteMappingVersion);
            // Created by the new mapping version cloning the previous one's starts
            $siteDungeonStart = DungeonStart::query()
                ->where('mapping_version_id', $siteMappingVersion->id)
                ->where('comment', 'Chosen start')
                ->firstOrFail();

            // Act
            $draft = $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
            array_unshift($this->cleanup, $draft);

            // Assert
            $this->assertSame($siteMappingVersion->id, $draft->mapping_version_id);
            $this->assertSame($siteDungeonStart->id, $draft->dungeon_start_id);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenStringForOtherDungeon_throwsAndPersistsNothing(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $otherDungeon         = Dungeon::query()
                ->where('id', '!=', $source->dungeon_id)
                ->whereHas('mappingVersions')
                ->firstOrFail();
            $original       = $this->createOriginal($otherDungeon->id, $otherDungeon->getCurrentMappingVersion()->id);
            $maxRouteId     = DungeonRoute::query()->max('id');
            $maxMdtImportId = MDTImport::query()->max('id');

            // Act
            $exception = null;

            try {
                $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
            } catch (UpgradeDraftException $upgradeDraftException) {
                $exception = $upgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(UpgradeDraftException::class, $exception);
            $this->assertStringContainsString(__($otherDungeon->name), $exception->getMessage());
            $this->assertSame($maxRouteId, DungeonRoute::query()->max('id'), 'No route may be persisted');
            $this->assertSame($maxMdtImportId, MDTImport::query()->max('id'), 'No import may be recorded');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenStringOnOutdatedMdtMapping_throwsAndPersistsNothing(): void
    {
        try {
            // Arrange
            [$source]               = $this->createSourceRouteAndString();
            $dungeon                = $source->dungeon;
            $outdatedMappingVersion = $source->mappingVersion;
            // A newer mapping version that MDT ships too, so the string's is outdated relative to it
            $this->cleanup[] = $this->createNewerMappingVersion($dungeon, $outdatedMappingVersion, mdtChangesPending: false);
            $original        = $this->createOriginal($dungeon->id, $outdatedMappingVersion->id);

            $mdtImportStringService = $this->createMockPublic(MDTImportStringServiceInterface::class);
            $mdtImportStringService->method('setEncodedString')->willReturnSelf();
            $mdtImportStringService->method('getDetails')->willReturn(new ImportStringDetails(
                collect(),
                collect(),
                $dungeon,
                collect(),
                false,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                $outdatedMappingVersion,
            ));
            $mdtImportStringService->expects($this->never())->method('getDungeonRoute');

            // Act
            $exception = null;

            try {
                $this->buildService(mdtImportStringService: $mdtImportStringService)
                    ->createDraftFromMdtString($original, 'an MDT string', collect());
            } catch (UpgradeDraftException $upgradeDraftException) {
                $exception = $upgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(UpgradeDraftException::class, $exception);
            $this->assertSame(__('services.dungeonroute.upgrade_draft.mdt_import_outdated_mapping'), $exception->getMessage());
            $this->assertNull($original->upgradeDraft()->first(), 'No draft may be persisted');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenOriginalOnNonDefaultGameVersion_importsOntoOriginalsGameVersion(): void
    {
        try {
            // Arrange
            [$dungeon, , $otherGameVersionMappingVersion] = $this->findDungeonOnRetailAndOtherGameVersion();
            $original                                     = $this->createOriginal($dungeon->id, $otherGameVersionMappingVersion->id);
            // Resolves the way the real import does: the given game version, otherwise the acting user's (retail)
            $resolveMappingVersion = static fn(?GameVersion $gameVersion): MappingVersion => $dungeon->getCurrentMappingVersion($gameVersion);

            $mdtImportStringService = $this->createMockPublic(MDTImportStringServiceInterface::class);
            $mdtImportStringService->method('setEncodedString')->willReturnSelf();
            $mdtImportStringService->method('getDetails')->willReturnCallback(
                fn(Collection $warnings, Collection $errors, ?GameVersion $gameVersion = null): ImportStringDetails => $this->buildImportStringDetails($dungeon, $resolveMappingVersion($gameVersion), collect()),
            );
            $mdtImportStringService->method('getDungeonRoute')->willReturnCallback(
                function (
                    Collection   $warnings,
                    Collection   $errors,
                    bool         $sandbox = false,
                    bool         $save = false,
                    bool         $assignNotesToPulls = true,
                    bool         $importAsThisWeek = false,
                    ?GameVersion $gameVersion = null,
                ) use ($dungeon, $original, $resolveMappingVersion): DungeonRoute {
                    $importedRoute = DungeonRoute::factory()->create([
                        'author_id'          => $original->author_id,
                        'dungeon_id'         => $dungeon->id,
                        'mapping_version_id' => $resolveMappingVersion($gameVersion)->id,
                        'expires_at'         => null,
                        'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
                    ]);
                    array_unshift($this->cleanup, $importedRoute);

                    return $importedRoute;
                },
            );

            // Act
            $draft = $this->buildService(mdtImportStringService: $mdtImportStringService)
                ->createDraftFromMdtString($original, 'an MDT string', collect());

            // Assert
            $this->assertSame($otherGameVersionMappingVersion->id, $draft->mapping_version_id, 'The draft stays on the original\'s game version');
            $this->assertSame($original->id, $draft->upgrade_of_dungeon_route_id);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenStringResolvedOnOtherGameVersion_throwsAndPersistsNothing(): void
    {
        try {
            // Arrange
            [$dungeon, $retailMappingVersion, $otherGameVersionMappingVersion] = $this->findDungeonOnRetailAndOtherGameVersion();
            $original                                                          = $this->createOriginal($dungeon->id, $otherGameVersionMappingVersion->id);
            $maxRouteId                                                        = DungeonRoute::query()->max('id');

            $mdtImportStringService = $this->createMockPublic(MDTImportStringServiceInterface::class);
            $mdtImportStringService->method('setEncodedString')->willReturnSelf();
            $mdtImportStringService->method('getDetails')->willReturn(
                $this->buildImportStringDetails($dungeon, $retailMappingVersion, collect()),
            );
            $mdtImportStringService->expects($this->never())->method('getDungeonRoute');

            // Act
            $exception = null;

            try {
                $this->buildService(mdtImportStringService: $mdtImportStringService)
                    ->createDraftFromMdtString($original, 'an MDT string', collect());
            } catch (UpgradeDraftException $upgradeDraftException) {
                $exception = $upgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(UpgradeDraftException::class, $exception);
            $this->assertSame(__('services.dungeonroute.upgrade_draft.mdt_import_other_game_version'), $exception->getMessage());
            $this->assertSame($maxRouteId, DungeonRoute::query()->max('id'), 'No route may be persisted');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenSiteAheadOfMdt_acceptsAndUpgradesDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            // A mapping version keystone.guru made that MDT does not ship (yet)
            $siteMappingVersion = $this->createNewerMappingVersion($source->dungeon, $source->mappingVersion, mdtChangesPending: true);
            array_unshift($this->cleanup, $siteMappingVersion);

            // Act
            $draft = $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
            array_unshift($this->cleanup, $draft);

            // Assert
            $this->assertSame($siteMappingVersion->id, $draft->mapping_version_id);
            $this->assertSame($original->id, $draft->upgrade_of_dungeon_route_id);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenPendingDraftWithoutConfirmation_throwsAndKeepsDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft        = $this->createExistingDraft($original);

            // Act
            $exception = null;

            try {
                $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
            } catch (PendingUpgradeDraftException $pendingUpgradeDraftException) {
                $exception = $pendingUpgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(PendingUpgradeDraftException::class, $exception);
            $this->assertNotNull(DungeonRoute::find($existingDraft->id), 'A pending draft is never discarded silently');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenPendingDraftWithConfirmation_replacesDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft        = $this->createExistingDraft($original);
            $this->createBrushlineForRoute($existingDraft);

            // Act
            $draft = $this->buildService()->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $existingDraft->id);
            array_unshift($this->cleanup, $draft);

            // Assert
            $this->assertNull(DungeonRoute::find($existingDraft->id));
            $this->assertSame(0, Brushline::query()->where('dungeon_route_id', $existingDraft->id)->count(), 'The discarded draft\'s content goes with it');
            $this->assertSame($original->id, $draft->upgrade_of_dungeon_route_id);
            $this->assertSame(DungeonRouteDraftSource::MdtImport, $draft->draft_source);
            $this->assertSame(2, $draft->killZones()->count(), 'The replacement holds the string\'s pulls');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenConfirmationForReplacedDraft_throwsStaleAndKeepsCurrentDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $draftA               = $this->createExistingDraft($original);
            $service              = $this->buildService();
            $draftB               = $service->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $draftA->id);
            array_unshift($this->cleanup, $draftB);
            $this->createBrushlineForRoute($draftB);
            $maxRouteId = DungeonRoute::query()->max('id');

            // Act
            $exception = null;

            try {
                $service->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $draftA->id);
            } catch (StaleUpgradeDraftException $staleUpgradeDraftException) {
                $exception = $staleUpgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(StaleUpgradeDraftException::class, $exception);
            $this->assertSame(__('services.dungeonroute.upgrade_draft.mdt_import_draft_changed'), $exception->getMessage());
            $this->assertSame($draftB->id, $original->upgradeDraft()->first()?->id, 'The draft that replaced the confirmed one must survive');
            $this->assertSame(1, $draftB->brushlines()->count(), 'The current draft keeps its edits');
            $this->assertFalse(
                DungeonRoute::query()->where('id', '>', $maxRouteId)->exists(),
                'A refused replacement leaves no route behind',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenDraftReplacedDuringImport_throwsStaleAndRemovesImportedRoute(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $draftA               = $this->createExistingDraft($original);
            $maxRouteId           = DungeonRoute::query()->max('id');
            $draftB               = null;

            // Another collaborator's replacement lands between this import's pre-check and its swap
            $dungeonRouteService = $this->createMockPublic(DungeonRouteServiceInterface::class);
            $dungeonRouteService->method('upgradeMappingVersion')->willReturnCallback(function () use ($draftA, $original, &$draftB): void {
                $draftA->delete();
                $draftB = $this->createExistingDraft($original);
            });
            array_unshift(
                $this->cleanup,
                $this->createNewerMappingVersion($source->dungeon, $source->mappingVersion, mdtChangesPending: true),
            );

            // Act
            $exception = null;

            try {
                $this->buildService($dungeonRouteService)
                    ->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $draftA->id);
            } catch (StaleUpgradeDraftException $staleUpgradeDraftException) {
                $exception = $staleUpgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(StaleUpgradeDraftException::class, $exception);
            $this->assertInstanceOf(DungeonRoute::class, $draftB);
            $this->assertSame($draftB->id, $original->upgradeDraft()->first()?->id, 'The concurrent replacement must survive');
            $this->assertFalse(
                DungeonRoute::query()->where('id', '>', $maxRouteId)->where('id', '!=', $draftB->id)->exists(),
                'The refused import must be removed again',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenImportFailingWithConfirmation_keepsExistingDraftAndContent(): void
    {
        try {
            // Arrange
            [$source]      = $this->createSourceRouteAndString();
            $original      = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft = $this->createExistingDraft($original);
            $this->createBrushlineForRoute($existingDraft);

            $mdtImportStringService = $this->createMockPublic(MDTImportStringServiceInterface::class);
            $mdtImportStringService->method('setEncodedString')->willReturnSelf();
            $mdtImportStringService->method('getDetails')->willReturn(
                $this->buildImportStringDetails($source->dungeon, $source->mappingVersion, collect()),
            );
            $mdtImportStringService->method('getDungeonRoute')->willThrowException(new RuntimeException('Import failed'));

            // Act
            $exception = null;

            try {
                $this->buildService(mdtImportStringService: $mdtImportStringService)
                    ->createDraftFromMdtString($original, 'an MDT string', collect(), discardExistingDraftId: $existingDraft->id);
            } catch (RuntimeException $runtimeException) {
                $exception = $runtimeException;
            }

            // Assert
            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertSame($existingDraft->id, $original->upgradeDraft()->first()?->id, 'The existing draft must survive a failed import');
            $this->assertSame(1, $existingDraft->brushlines()->count(), 'The existing draft keeps its content');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenImportFailingAfterPullsPersisted_removesPartialRouteAndKeepsExistingDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft        = $this->createExistingDraft($original);
            $this->createBrushlineForRoute($existingDraft);

            $objectImporter = $this->getMockBuilder(ObjectImporter::class)
                ->setConstructorArgs([
                    app(CoordinatesServiceInterface::class),
                    app(MDTImportStringServiceLoggingInterface::class),
                ])
                ->onlyMethods(['applyObjectsToDungeonRoute'])
                ->getMock();
            $objectImporter->method('applyObjectsToDungeonRoute')->willThrowException(new RuntimeException('Object persistence failed'));
            $mdtImportStringService = app()->make(MDTImportStringService::class, ['objectImporter' => $objectImporter]);
            $maxRouteId             = DungeonRoute::query()->max('id');

            // Act
            $exception = null;

            try {
                $this->buildService(mdtImportStringService: $mdtImportStringService)
                    ->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $existingDraft->id);
            } catch (RuntimeException $runtimeException) {
                $exception = $runtimeException;
            }

            // Assert
            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertSame('Object persistence failed', $exception->getMessage());
            $this->assertFalse(
                DungeonRoute::query()->where('id', '>', $maxRouteId)->exists(),
                'The partially imported route must not survive the failure',
            );
            $this->assertFalse(
                KillZone::query()->where('dungeon_route_id', '>', $maxRouteId)->exists(),
                'Nor may the pulls it had already persisted',
            );
            $this->assertSame($existingDraft->id, $original->upgradeDraft()->first()?->id, 'The existing draft must survive a failed import');
            $this->assertSame(1, $existingDraft->brushlines()->count(), 'The existing draft keeps its content');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenMappingUpgradeFailingWithConfirmation_keepsExistingDraftAndContent(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft        = $this->createExistingDraft($original);
            $this->createBrushlineForRoute($existingDraft);
            array_unshift(
                $this->cleanup,
                $this->createNewerMappingVersion($source->dungeon, $source->mappingVersion, mdtChangesPending: true),
            );
            $dungeonRouteService = $this->createMockPublic(DungeonRouteServiceInterface::class);
            $dungeonRouteService->method('upgradeMappingVersion')->willThrowException(new RuntimeException('Upgrade failed'));
            $maxRouteId = DungeonRoute::query()->max('id');

            // Act
            $exception = null;

            try {
                $this->buildService($dungeonRouteService)
                    ->createDraftFromMdtString($original, $mdtString, collect(), discardExistingDraftId: $existingDraft->id);
            } catch (RuntimeException $runtimeException) {
                $exception = $runtimeException;
            }

            // Assert
            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertSame($existingDraft->id, $original->upgradeDraft()->first()?->id, 'The existing draft must survive a failed upgrade');
            $this->assertSame(1, $existingDraft->brushlines()->count(), 'The existing draft keeps its content');
            $this->assertFalse(
                DungeonRoute::query()->where('id', '>', $maxRouteId)->exists(),
                'The imported replacement must be removed again',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenPreviewErrorsWithConfirmation_throwsAndKeepsExistingDraft(): void
    {
        try {
            // Arrange
            [$source]      = $this->createSourceRouteAndString();
            $original      = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft = $this->createExistingDraft($original);
            $this->createBrushlineForRoute($existingDraft);

            $mdtImportStringService = $this->createMockPublic(MDTImportStringServiceInterface::class);
            $mdtImportStringService->method('setEncodedString')->willReturnSelf();
            $mdtImportStringService->method('getDetails')->willReturn($this->buildImportStringDetails(
                $source->dungeon,
                $source->mappingVersion,
                collect([new ImportError('pulls', 'Unable to find the enemy.')]),
            ));
            $mdtImportStringService->expects($this->never())->method('getDungeonRoute');

            // Act
            $exception = null;

            try {
                $this->buildService(mdtImportStringService: $mdtImportStringService)
                    ->createDraftFromMdtString($original, 'an MDT string', collect(), discardExistingDraftId: $existingDraft->id);
            } catch (UpgradeDraftException $upgradeDraftException) {
                $exception = $upgradeDraftException;
            }

            // Assert
            $this->assertInstanceOf(UpgradeDraftException::class, $exception);
            $this->assertStringContainsString('Unable to find the enemy.', $exception->getMessage());
            $this->assertSame($existingDraft->id, $original->upgradeDraft()->first()?->id, 'A rejected string may not discard the draft');
            $this->assertSame(1, $existingDraft->brushlines()->count(), 'The existing draft keeps its content');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenSandboxRoute_throwsUpgradeDraftException(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id, [
                'expires_at' => now()->addHour(),
            ]);

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenDraft_throwsUpgradeDraftException(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            $existingDraft        = $this->createExistingDraft($original);

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildService()->createDraftFromMdtString($existingDraft, $mdtString, collect());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenMappingUpgradeFailing_removesImportedRoute(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $original             = $this->createOriginal($source->dungeon_id, $source->mapping_version_id);
            array_unshift(
                $this->cleanup,
                $this->createNewerMappingVersion($source->dungeon, $source->mappingVersion, mdtChangesPending: true),
            );
            $dungeonRouteService = $this->createMockPublic(DungeonRouteServiceInterface::class);
            $dungeonRouteService->method('upgradeMappingVersion')->willThrowException(new RuntimeException('Upgrade failed'));
            $maxRouteId = DungeonRoute::query()->max('id');

            // Act
            $exception = null;

            try {
                $this->buildService($dungeonRouteService)->createDraftFromMdtString($original, $mdtString, collect());
            } catch (RuntimeException $runtimeException) {
                $exception = $runtimeException;
            }

            // Assert
            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertFalse(
                DungeonRoute::query()->where('id', '>', $maxRouteId)->exists(),
                'The imported route must be removed again',
            );
            $this->assertNull($original->upgradeDraft()->first());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function createDraftFromMdtString_givenTeamRoute_keepsTeamAndLetsCollaboratorEditDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $collaborator         = User::factory()->create();
            $this->cleanup[]      = $collaborator;
            $team                 = Team::create([
                'name'         => sprintf('Draft test %s', uniqid()),
                'public_key'   => Team::generateRandomPublicKey(),
                'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
                'description'  => 'Created by DungeonRouteUpgradeDraftServiceMdtImportTest',
                'icon_file_id' => -1,
                'default_role' => TeamUser::ROLE_MEMBER,
            ]);
            $this->cleanup[] = $team;
            TeamUser::create([
                'team_id' => $team->id,
                'user_id' => $collaborator->id,
                'role'    => TeamUser::ROLE_COLLABORATOR,
            ]);
            $original = $this->createOriginal($source->dungeon_id, $source->mapping_version_id, [
                'team_id'            => $team->id,
                'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            ]);

            // Act
            $draft = $this->buildService()->createDraftFromMdtString($original, $mdtString, collect());
            array_unshift($this->cleanup, $draft);

            // Assert
            $this->assertSame($team->id, $draft->team_id);
            $this->assertTrue($collaborator->can('edit', $draft->fresh()), 'A team collaborator must be able to open the draft');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function getMdtImportContentLoss_givenRouteWithBrushline_returnsOnlyPresentKinds(): void
    {
        try {
            // Arrange
            $original = $this->createOriginal(...$this->anyDungeonAndMappingVersion());
            $this->createBrushlineForRoute($original);

            // Act
            $loss = $this->buildService()->getMdtImportContentLoss($original);

            // Assert
            $this->assertSame([DungeonRouteUpgradeDraftService::MDT_IMPORT_LOSS_BRUSHLINES => 1], $loss);
        } finally {
            $this->tearDownCleanup();
        }
    }

    private function buildService(
        ?DungeonRouteServiceInterface    $dungeonRouteService = null,
        ?MDTImportStringServiceInterface $mdtImportStringService = null,
    ): DungeonRouteUpgradeDraftService {
        return new DungeonRouteUpgradeDraftService(
            $dungeonRouteService ?? app(DungeonRouteServiceInterface::class),
            $this->createMockPublic(ThumbnailServiceInterface::class),
            $this->createMockPublic(DungeonRouteUpgradeDraftServiceLoggingInterface::class),
            $mdtImportStringService ?? app(MDTImportStringServiceInterface::class),
            app(MappingServiceInterface::class),
            app(DungeonStartRepositoryInterface::class),
        );
    }

    /**
     * A route with two pulls, exported to an MDT string. The string carries no addonVersion, so it resolves
     * to the dungeon's newest MDT-synced mapping version.
     *
     * @return array{0: DungeonRoute, 1: string}
     */
    private function createSourceRouteAndString(): array
    {
        $source = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(2, ['expires_at' => null]);
        array_unshift($this->cleanup, $source);

        $enemies = $this->getSafeMdtEnemies($source, 2);
        foreach ([1, 2] as $index) {
            KillZone::factory()->withEnemies($enemies->get($index - 1))->create([
                'dungeon_route_id' => $source->id,
                'index'            => $index,
                'description'      => null,
            ]);
        }

        return [$source, $this->exportDungeonRouteToString($source)];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createOriginal(int $dungeonId, int $mappingVersionId, array $attributes = []): DungeonRoute
    {
        $author          = User::factory()->create();
        $this->cleanup[] = $author;

        $original = DungeonRoute::factory()->create(array_merge([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeonId,
            'mapping_version_id' => $mappingVersionId,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'title'              => 'The original title',
            'description'        => 'The original description',
            'level_min'          => 12,
            'level_max'          => 14,
            'difficulty'         => 'Hardcore',
            'pull_gradient'      => '10 #ff0000,90 #00ff00',
        ], $attributes));
        array_unshift($this->cleanup, $original);

        DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $original->id,
            'affix_group_id'   => AffixGroup::query()->orderByDesc('id')->value('id'),
        ]);

        return $original;
    }

    private function createDungeonStart(MappingVersion $mappingVersion, string $comment): DungeonStart
    {
        $dungeonStart = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors()->firstOrFail()->id,
            'comment'            => $comment,
        ]);
        array_unshift($this->cleanup, $dungeonStart);

        return $dungeonStart;
    }

    private function createExistingDraft(DungeonRoute $original): DungeonRoute
    {
        $draft = DungeonRoute::factory()->create([
            'author_id'                   => $original->author_id,
            'dungeon_id'                  => $original->dungeon_id,
            'mapping_version_id'          => $original->mapping_version_id,
            'upgrade_of_dungeon_route_id' => $original->id,
            'draft_source'                => DungeonRouteDraftSource::MappingUpgrade,
            'expires_at'                  => null,
            'published_state_id'          => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
        array_unshift($this->cleanup, $draft);

        return $draft;
    }

    private function createNewerMappingVersion(Dungeon $dungeon, MappingVersion $existing, bool $mdtChangesPending): MappingVersion
    {
        $mappingVersion = MappingVersion::create([
            'game_version_id'                 => $existing->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + 1000,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
            'mdt_changes_pending'             => $mdtChangesPending,
        ]);

        $dungeon->reloadMappingVersions();

        return $mappingVersion;
    }

    /**
     * @param Collection<int, ImportError> $errors
     */
    private function buildImportStringDetails(Dungeon $dungeon, MappingVersion $mappingVersion, Collection $errors): ImportStringDetails
    {
        return new ImportStringDetails(
            collect(),
            $errors,
            $dungeon,
            collect(),
            false,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            $mappingVersion,
        );
    }

    /**
     * @return array{0: Dungeon, 1: MappingVersion, 2: MappingVersion} A dungeon mapped for both retail and another
     *                                                                 game version, with its current mapping version
     *                                                                 of each.
     */
    private function findDungeonOnRetailAndOtherGameVersion(): array
    {
        $retail = GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);

        /** @var MappingVersion $otherGameVersionMappingVersion */
        $otherGameVersionMappingVersion = MappingVersion::query()
            ->with(['dungeon', 'gameVersion'])
            ->where('game_version_id', '!=', $retail->id)
            ->whereIn('dungeon_id', MappingVersion::query()->select('dungeon_id')->where('game_version_id', $retail->id))
            ->orderBy('id')
            ->firstOrFail();

        $dungeon = $otherGameVersionMappingVersion->dungeon;

        return [
            $dungeon,
            $dungeon->getCurrentMappingVersionForGameVersion($retail),
            $dungeon->getCurrentMappingVersionForGameVersion($otherGameVersionMappingVersion->gameVersion),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function anyDungeonAndMappingVersion(): array
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()->orderByDesc('id')->firstOrFail();

        return [$mappingVersion->dungeon_id, $mappingVersion->id];
    }

    private function tearDownCleanup(): void
    {
        /** @var Collection<int, Model> $models */
        $models = collect($this->cleanup);
        foreach ($models as $model) {
            if ($model instanceof DungeonRoute) {
                // Also covers drafts created by the service that the test never got a handle on
                DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $model->id)->get()->each->delete();
            }

            $model->fresh()?->delete();
        }

        $this->cleanup = [];
    }
}
