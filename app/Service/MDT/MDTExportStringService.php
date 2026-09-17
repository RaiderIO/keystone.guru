<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\IO\MDTStringFormat;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\MDT\Export\ArrowExporter;
use App\Service\MDT\Export\EnemyAssignmentExporter;
use App\Service\MDT\Export\KillZoneDescriptionExporter;
use App\Service\MDT\Export\KillZoneSpellsExporter;
use App\Service\MDT\Export\LineExporter;
use App\Service\MDT\Export\MapIconExporter;
use App\Service\MDT\Export\MDTObjectExporterInterface;
use App\Service\MDT\Export\PullExporter;
use App\Service\MDT\Logging\MDTExportStringServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 *
 * @author Wouter
 *
 * @since 09/11/2022
 */
class MDTExportStringService extends MDTBaseService implements MDTExportStringServiceInterface
{
    use RemembersToFile;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private DungeonRoute $dungeonRoute;

    /** @var array<int, MDTObjectExporterInterface> The order MDT's objects are numbered in. */
    private readonly array $objectExporters;

    public function __construct(
        MapIconExporter                          $mapIconExporter,
        LineExporter                             $lineExporter,
        ArrowExporter                            $arrowExporter,
        KillZoneDescriptionExporter              $killZoneDescriptionExporter,
        KillZoneSpellsExporter                   $killZoneSpellsExporter,
        private readonly PullExporter            $pullExporter,
        private readonly EnemyAssignmentExporter $enemyAssignmentExporter,
        MDTExportStringServiceLoggingInterface   $log,
    ) {
        parent::__construct($log);

        $this->objectExporters = [
            $mapIconExporter,
            $lineExporter,
            $arrowExporter,
            $killZoneDescriptionExporter,
            $killZoneSpellsExporter,
        ];
    }

    /**
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    private function extractObjects(Collection $warnings): array
    {
        $result = [];

        // Lua is 1 based, not 0 based
        $currentObjectIndex = 1;

        foreach ($this->objectExporters as $objectExporter) {
            foreach ($objectExporter->export($this->dungeonRoute, $warnings) as $item) {
                $result[$currentObjectIndex++] = $item;
            }
        }

        return $result;
    }

    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @throws Exception
     */
    public function getEncodedString(Collection $warnings, bool $useCache = true): string
    {
        return $this->rememberLocal(
            // Spell names in the string are translated to the current locale
            sprintf(
                'mdt_export_string:%s_%s_%s',
                $this->dungeonRoute->id,
                $this->dungeonRoute->updated_at->timestamp,
                app()->getLocale(),
            ),
            config('keystoneguru.cache.mdt_export_strings.ttl'),
            function () use ($warnings) {
                //        $lua = $this->_getLua();

                $this->dungeonRoute->loadMissing([
                    'affixGroups',
                    'dungeon',
                ]);

                $affixes = $this->dungeonRoute->affixes()->with(['season'])->get();
                /** @var AffixGroup|null $firstAffixGroup */
                $firstAffixGroup = $affixes->first();

                $mdtObject = [
                    //
                    'objects' => $this->extractObjects($warnings),
                    // M+ level
                    'difficulty' => $this->dungeonRoute->level_min,
                    'week'       => $this->dungeonRoute->affixGroups->isEmpty() || $affixes->isEmpty() || $firstAffixGroup === null ? 1 :
                        Conversion::convertAffixGroupToWeek($firstAffixGroup),
                    'value' => [
                        'currentDungeonIdx' => $this->dungeonRoute->dungeon->mdt_id,
                        'selection'         => [],
                        'currentPull'       => 1,
                        'teeming'           => $this->dungeonRoute->teeming,
                        // Legacy - we don't do anything with it
                        'riftOffsets' => [

                        ],
                        'pulls'            => $this->pullExporter->export($this->dungeonRoute, $this->dungeonRoute->mappingVersion, $warnings),
                        'enemyAssignments' => $this->enemyAssignmentExporter->export($this->dungeonRoute, $this->dungeonRoute->mappingVersion, $warnings),
                        'currentSublevel'  => 1,
                    ],
                    'text' => $this->dungeonRoute->title,
                    'mdi'  => [
                        'freeholdJoined' => false,
                        'freehold'       => 1,
                        'beguiling'      => 1,
                    ],
                    // Leave a consistent UID so multiple imports overwrite eachother - and a little watermark
                    'uid' => $this->dungeonRoute->public_key . 'xxKG',
                ];

                try {
                    return $this->encode($mdtObject, MDTStringFormat::MDT2);
                } catch (Exception $exception) {
                    // Encoding issue - adjust the title and try again
                    if (str_contains($exception->getMessage(), 'call to lua function [string &quot;line&quot;]')) {
                        $asciiTitle = preg_replace('/[[:^print:]]/', '', $this->dungeonRoute->title);

                        // If stripping ascii characters worked in changing the title somehow
                        if ($asciiTitle !== $this->dungeonRoute->title) {
                            $warnings->push(
                                new ImportWarning(
                                    __('services.mdt.io.export_string.category.title'),
                                    __('services.mdt.io.export_string.route_title_contains_non_ascii_char_bug'),
                                    ['details' => sprintf(__('services.mdt.io.export_string.route_title_contains_non_ascii_char_bug_details'), $this->dungeonRoute->title, $asciiTitle)],
                                ),
                            );
                            $this->dungeonRoute->title = $asciiTitle;

                            return $this->getEncodedString($warnings);
                        } else {
                            $fixedMapIconComment = false;

                            foreach ($this->dungeonRoute->mapicons as $mapIcon) {
                                $asciiComment = preg_replace('/[[:^print:]]/', '', $mapIcon->comment ?? '');
                                if ($asciiComment !== $mapIcon->comment) {
                                    $warnings->push(
                                        new ImportWarning(
                                            __('services.mdt.io.export_string.category.map_icon'),
                                            __('services.mdt.io.export_string.map_icon_contains_non_ascii_char_bug'),
                                            ['details' => sprintf(__('services.mdt.io.export_string.map_icon_contains_non_ascii_char_bug_details'), $asciiComment, $mapIcon->comment)],
                                        ),
                                    );
                                    $mapIcon->comment = $asciiComment;

                                    $fixedMapIconComment = true;
                                }
                            }

                            // If we fixed something, try again with encoding
                            if ($fixedMapIconComment) {
                                return $this->getEncodedString($warnings);
                            } else {
                                throw $exception;
                            }
                        }
                    } else {
                        throw $exception;
                    }
                }
            },
            $useCache,
        );
    }

    /**
     * Sets a dungeon route to be staged for encoding to an encoded string.
     *
     * @param        $dungeonRoute DungeonRoute
     * @return $this Returns self to allow for chaining.
     */
    public function setDungeonRoute(DungeonRoute $dungeonRoute): self
    {
        $this->dungeonRoute = $dungeonRoute;

        return $this;
    }
}
