<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Logic\MDT\Exception\MDTStringParseException;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\MDTImport;
use App\Models\PublishedState;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Import\PullImporter;
use App\Service\MDT\Import\RiftOffsetImporter;
use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\Models\ImportStringDetails;
use App\Service\MDT\Models\ImportStringObjects;
use App\Service\MDT\Models\ImportStringPulls;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Class MDTImportStringService
 *
 * @author Wouter
 *
 * @since 09/11/2022
 */
class MDTImportStringService extends MDTBaseService implements MDTImportStringServiceInterface
{
    /** @var string The MDT encoded string that's currently staged for conversion to a DungeonRoute. */
    private string $encodedString;

    public function __construct(
        /** @var SeasonServiceInterface Used for grabbing info about the current M+ season. */
        private readonly SeasonServiceInterface                 $seasonService,
        private readonly SeasonAffixGroupServiceInterface       $seasonAffixGroupService,
        private readonly MDTImportStringServiceLoggingInterface $log,
        private readonly PullImporter                           $pullImporter,
        private readonly ObjectImporter                         $objectImporter,
        private readonly RiftOffsetImporter                     $riftOffsetImporter,
    ) {
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @param  array<string, mixed>           $decoded
     * @throws Exception
     */
    private function parseAffixes(
        Collection $warnings,
        array      $decoded,
        Dungeon    $dungeon,
        bool       $importAsThisWeek = false,
    ): ?AffixGroup {
        $affixGroup = Conversion::convertWeekToAffixGroup($this->seasonService, $dungeon, $decoded['week']);

        // If affix group not found or
        if ($importAsThisWeek || $affixGroup === null) {
            $activeSeason = $dungeon->getActiveSeason($this->seasonService);
            if ($activeSeason !== null) {
                $affixGroup = $this->seasonAffixGroupService->getCurrentAffixGroup($activeSeason);
            }
        }

        return $affixGroup;
    }

    /**
     * Gets an array that represents the currently set MDT string.
     * @return array<string, mixed>|null
     */
    public function getDecoded(): ?array
    {
        return $this->decode($this->encodedString);
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @param  Collection<int, ImportError>   $errors
     * @throws InvalidMDTDungeonException
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     */
    public function getDetails(Collection $warnings, Collection $errors): ImportStringDetails
    {
        try {
            $this->log->getDetailsStart();

            $decoded = $this->decode($this->encodedString);

            if ($decoded === null) {
                throw new MDTStringParseException('Unable to decode MDT import string');
            }

            // Check if it's valid
            /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
            $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

            if (!$isValid) {
                throw new InvalidMDTStringException('Unable to validate MDT import string in Lua');
            }

            $warnings = collect();
            $errors   = collect();

            $dungeon = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);
            // Preview against the same mapping version the actual import will use, so the stats match (#3380).
            $mappingVersion = $dungeon->getMappingVersionForMdtAddonVersion(
                isset($decoded['addonVersion']) ? (int)$decoded['addonVersion'] : null,
            );

            /** @var AffixGroup|null $affixGroup */
            $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeon);

            $importStringPulls = $this->pullImporter->parseValuePulls(new ImportStringPulls(
                $warnings,
                $errors,
                $dungeon,
                $mappingVersion,
                $affixGroup?->hasAffix(Affix::AFFIX_TEEMING) ?? false,
                null,
                $decoded['value']['pulls'],
            ));

            $importStringObjects = $this->objectImporter->parseObjects(new ImportStringObjects(
                $warnings,
                $errors,
                $dungeon,
                $mappingVersion,
                $importStringPulls->getKillZoneAttributes(),
                $decoded['objects'],
            ), false);

            $currentSeason               = $this->seasonService->getCurrentSeason($dungeon->expansion);
            $currentAffixGroupForDungeon = $currentSeason !== null ? $this->seasonAffixGroupService->getCurrentAffixGroup($currentSeason) : null;

            return new ImportStringDetails(
                $warnings,
                $errors,
                $dungeon,
                collect([$affixGroup?->getTextAttribute() ?? '']),
                $affixGroup !== null && $currentAffixGroupForDungeon !== null &&
                $affixGroup->id === $currentAffixGroupForDungeon->id,
                $importStringPulls->getKillZoneAttributes()->count(),
                $importStringObjects->getPaths()->count(),
                $importStringObjects->getLines()->count(),
                $importStringObjects->getArrows()->count(),
                $importStringObjects->getMapIcons()->count(),
                $importStringPulls->getEnemyForces(),
                $importStringPulls->isRouteTeeming() ?
                    $importStringPulls->getMappingVersion()->enemy_forces_required_teeming :
                    $importStringPulls->getMappingVersion()->enemy_forces_required,
            );
        } finally {
            $this->log->getDetailsEnd();
        }
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     *
     * @param                                 $warnings         Collection Collection that is passed by reference in which any warnings are stored.
     * @param  Collection<int, ImportWarning> $warnings
     * @param  Collection<int, ImportError>   $errors
     * @param                                 $sandbox          boolean True to mark the dungeon as a sandbox route which will be automatically deleted at a later stage.
     * @param                                 $save             bool True to save the route and all associated models, false to not save & couple.
     * @param                                 $importAsThisWeek bool True to replace the imported affixes with this week's affixes instead
     * @return DungeonRoute                   DungeonRoute if the route could be constructed
     *
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     * @throws Exception
     */
    public function getDungeonRoute(
        Collection $warnings,
        Collection $errors,
        bool       $sandbox = false,
        bool       $save = false,
        bool       $assignNotesToPulls = true,
        bool       $importAsThisWeek = false,
    ): DungeonRoute {
        $error = null;

        // Keep track of the import
        $mdtImport = MDTImport::create([
            'dungeon_route_id' => null,
            'import_string'    => $this->encodedString,
        ]);

        try {
            $this->log->getDungeonRouteStart($sandbox, $save, $importAsThisWeek);

            $decoded = $this->decode($this->encodedString);

            if ($decoded === null) {
                $error = __('services.mdt.io.import_string.unable_to_decode_mdt_import_string');

                throw new MDTStringParseException($error);
            }

            // Check if it's valid
            /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
            $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

            if (!$isValid) {
                $error = __('services.mdt.io.import_string.unable_to_validate_mdt_import_string');

                throw new InvalidMDTStringException($error);
            }

            $dungeon = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);
            // Attach the route to the mapping version matching the MDT version the string was built with,
            // so routes imported from older strings are flagged as outdated and offered an upgrade (#3380).
            $currentMappingVersion = $dungeon->getMappingVersionForMdtAddonVersion(
                isset($decoded['addonVersion']) ? (int)$decoded['addonVersion'] : null,
            );

            // Create a dungeon route
            $titleSlug    = Str::slug($decoded['text']);
            $season       = $this->seasonService->getMostRecentSeasonForDungeon($dungeon);
            $dungeonRoute = DungeonRoute::create([
                'author_id'          => $sandbox ? -1 : Auth::id() ?? -1,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $currentMappingVersion->id,
                'season_id'          => $season?->id,
                // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
                'faction_id' => isset($decoded['faction']) ?
                    ((int)$decoded['faction'] === 1 ? Faction::ALL[Faction::FACTION_HORDE] : Faction::ALL[Faction::FACTION_ALLIANCE])
                    : Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
                // Needs to be explicit otherwise redirect to edit will not have this value
                'public_key' => DungeonRoute::generateRandomPublicKey(),
                'teeming'    => boolval($decoded['value']['teeming'] ?? false),
                'title'      => empty($titleSlug) ? __($dungeon->name, [], 'en_US') : $decoded['text'],
                'difficulty' => 'Casual',
                'level_min'  => $decoded['difficulty'] ?? $season?->key_level_min ?? 2, // @phpstan-ignore nullsafe.neverNull
                'level_max'  => $decoded['difficulty'] ?? $season?->key_level_max ?? 2, // @phpstan-ignore nullsafe.neverNull
                'expires_at' => $sandbox ? Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString() : null,
            ]);

            // Set some relations here so we can reference them later
            $dungeonRoute->setRelation('dungeon', $dungeon);
            $dungeonRoute->setRelation('mappingVersion', $currentMappingVersion);

            // Set the affix for this route
            $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeonRoute->dungeon, $importAsThisWeek);

            $this->applyAffixGroupToDungeonRoute($affixGroup, $dungeonRoute);

            // Create a path and map icons for MDT rift offsets
            if (isset($decoded['value']['riftOffsets'])) {
                $importStringRiftOffsets = $this->riftOffsetImporter->parseRiftOffsets(new ImportStringRiftOffsets(
                    $warnings,
                    $dungeon,
                    $currentMappingVersion,
                    $dungeonRoute->seasonal_index,
                    $decoded['value']['riftOffsets'],
                    $decoded['week'],
                ));

                $this->riftOffsetImporter->applyRiftOffsetsToDungeonRoute($importStringRiftOffsets, $dungeonRoute);
            }

            // Create killzones and attach enemies
            $importStringPulls = $this->pullImporter->parseValuePulls(new ImportStringPulls(
                $warnings,
                $errors,
                $dungeonRoute->dungeon,
                $dungeonRoute->mappingVersion,
                $dungeonRoute->teeming,
                $dungeonRoute->seasonal_index,
                $decoded['value']['pulls'],
            ));

            // For each object the user created
            $importStringObjects = $this->objectImporter->parseObjects(new ImportStringObjects(
                $warnings,
                $errors,
                $dungeonRoute->dungeon,
                $dungeonRoute->mappingVersion,
                $importStringPulls->getKillZoneAttributes(),
                $decoded['objects'],
            ), $assignNotesToPulls);

            if ($errors->isNotEmpty()) {
                // Get rid of it again!
                $dungeonRoute->delete();

                throw new InvalidMDTStringException('Unable to MDT import string - there have been errors converting your string to a route');
            } else {
                // Only after parsing objects too since they may adjust the pulls before inserting
                $this->pullImporter->applyPullsToDungeonRoute($importStringPulls, $dungeonRoute);

                $this->objectImporter->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute);

                // Successfully imported!
                $mdtImport->update(['dungeon_route_id' => $dungeonRoute->id]);
            }

            return $dungeonRoute;
        } finally {
            if ($error !== null) {
                $mdtImport->update(['error' => $error]);
            }

            $this->log->getDungeonRouteEnd();
        }
    }

    private function applyAffixGroupToDungeonRoute(?AffixGroup $affixGroup, DungeonRoute $dungeonRoute): void
    {
        if ($affixGroup === null) {
            return;
        }

        // Something we can save to the database
        DungeonRouteAffixGroup::create([
            'affix_group_id'   => $affixGroup->id,
            'dungeon_route_id' => $dungeonRoute->id,
        ]);

        // Apply the seasonal index to the route
        $dungeonRoute->update(['seasonal_index' => $affixGroup->seasonal_index]);
    }

    /**
     * Sets the encoded string to be staged for translation to a DungeonRoute.
     *
     * @param $encodedString string The MDT encoded string.
     */
    public function setEncodedString(string $encodedString): self
    {
        $this->encodedString = $encodedString;

        $this->log->setEncodedStringEncodedString($encodedString);

        return $this;
    }
}
