<?php

namespace App\Service\Spell\Description;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellEffect;
use App\Repositories\Interfaces\Spell\SpellDescriptionImportStateRepositoryInterface;
use App\Service\Spell\Description\Dtos\SpellDescriptionImportResult;
use App\Service\Spell\Description\Dtos\SpellDescriptionTemplates;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use App\Service\Spell\Description\Dtos\SpellEffectData;
use App\Service\Spell\Description\Logging\SpellDescriptionImportServiceLoggingInterface;
use App\Service\WagoTools\WagoToolsServiceInterface;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Builds the descriptions of every spell we know from the game client's DB2 tables.
 *
 * The DB2 tables themselves are never stored: they are hundreds of thousands of rows of which we need a
 * few thousand, and nothing about a rendered description varies per request. They are streamed once,
 * used to render, and thrown away - re-rendering later only needs the template we keep alongside the
 * description.
 */
class SpellDescriptionImportService implements SpellDescriptionImportServiceInterface
{
    private const int PROGRESS_INTERVAL = 250;

    private const int PERSIST_CHUNK_SIZE = 500;

    /**
     * How often the Spell table is re-read to follow the references a description makes to other
     * spells. Chains beyond this are as deep as the parser itself is willing to recurse.
     */
    private const int MAX_REFERENCE_PASSES = 3;

    public function __construct(
        private readonly WagoToolsServiceInterface                      $wagoToolsService,
        private readonly SpellDescriptionParserInterface                $spellDescriptionParser,
        private readonly SpellDescriptionImportStateRepositoryInterface $spellDescriptionImportStateRepository,
        private readonly SpellDescriptionImportServiceLoggingInterface  $log,
    ) {
    }

    public function importDescriptions(
        string   $product,
        int      $gameVersionId,
        ?string  $build = null,
        ?Closure $onProgress = null,
    ): ?SpellDescriptionImportResult {
        $build ??= $this->wagoToolsService->getLatestBuild($product);

        if ($build === null) {
            $this->log->importDescriptionsUnknownBuild($product);

            return null;
        }

        try {
            $this->log->importDescriptionsStart($product, $build, $gameVersionId);

            // A build belongs to one game client, so only the spells of that client's game version are
            // its business - a retail build knows nothing about the spells of the classic ones.
            /** @var Collection<int, Spell> $spells */
            $spells = Spell::query()
                ->select(['id', 'description_template', 'description_format', 'description_values', 'damage_multiplier'])
                ->where('game_version_id', $gameVersionId)
                ->get();
            $ourIds = array_fill_keys($spells->pluck('id')->all(), true);

            $templates = $this->readDescriptionTemplates($build, $ourIds);

            // A build whose Spell table has nothing to say about any spell we know is not a build we can
            // import - carrying on would clear every description we have on the strength of a bad file.
            if ($templates->described === [] && $spells->isNotEmpty()) {
                $this->log->importDescriptionsNoDescriptionsFound($product, $build);

                return null;
            }

            $wantedIds = $templates->getWantedIds($ourIds);
            $effects   = $this->readEffects($build, $wantedIds);

            $context = new ArraySpellDescriptionContext(
                effects: $effects,
                durationsMs: $this->readDurations($build, $wantedIds),
                names: $this->readNames($build, $wantedIds),
                templates: $templates->described,
                descriptionVariables: $this->readDescriptionVariables($build),
            );

            $this->persistEffects(array_intersect_key($effects, $ourIds));

            $updatedCount = $this->renderAndPersist($context, $spells, $templates, $onProgress);

            $this->spellDescriptionImportStateRepository->recordImport($gameVersionId, $product, $build, Carbon::now());

            return new SpellDescriptionImportResult(
                build: $build,
                spellCount: $spells->count(),
                describedCount: count(array_intersect_key($templates->described, $ourIds)),
                updatedCount: $updatedCount,
            );
        } finally {
            $this->log->importDescriptionsEnd();
        }
    }

    /**
     * Store the coefficients themselves, not just the sentence they were rendered into - they are what a
     * later key level recalculates from.
     *
     * @param array<int, array<int, SpellEffectData>> $effects
     */
    private function persistEffects(array $effects): void
    {
        $rows = [];

        foreach ($effects as $spellId => $spellEffects) {
            foreach ($spellEffects as $effectIndex => $effect) {
                // The effect names its own columns, so a field added to it cannot miss this call
                $rows[] = $effect->toArray($spellId, $effectIndex);
            }
        }

        foreach (array_chunk($rows, self::PERSIST_CHUNK_SIZE) as $chunk) {
            SpellEffect::query()->upsert($chunk, ['spell_id', 'effect_index']);
        }

        // An upsert never deletes, so an effect a patch took away would otherwise linger forever - and
        // ride into every environment through the seeder (#3972 review)
        foreach ($effects as $spellId => $spellEffects) {
            SpellEffect::query()
                ->where('spell_id', $spellId)
                ->whereNotIn('effect_index', array_keys($spellEffects))
                ->delete();
        }

        new SpellEffect()->flushCache();
    }

    /**
     * @param Collection<int, Spell>       $spells
     * @param Closure(int, int): void|null $onProgress
     */
    private function renderAndPersist(
        SpellDescriptionContextInterface $context,
        Collection                       $spells,
        SpellDescriptionTemplates        $templates,
        ?Closure                         $onProgress,
    ): int {
        $updatedCount = 0;
        $handled      = 0;
        $total        = $spells->count();

        foreach ($spells as $spell) {
            $handled++;

            // A build that has never heard of the spell has no opinion on it either way; leave what we
            // have rather than clearing it
            if (!$templates->isPresent($spell->id)) {
                continue;
            }

            $template = $templates->described[$spell->id] ?? null;

            // A spell that lost its description in this build must lose ours as well
            $description = $template === null
                ? null
                : $this->spellDescriptionParser->parse($context, $spell->id, $template, $spell->damage_multiplier ?? 0.0);

            $format = $description === null || $description->isEmpty() ? null : $description->format;
            $values = $format === null ? null : array_map(
                static fn(SpellDescriptionValue $value): array => $value->toArray(),
                $description->values,
            );

            if ($template !== $spell->description_template
                || $format !== $spell->description_format
                || $values !== $spell->description_values) {
                Spell::query()
                    ->whereKey($spell->id)
                    ->update([
                        'description_template' => $template,
                        'description_format'   => $format,
                        'description_values'   => $values === null ? null : json_encode($values),
                    ]);

                $updatedCount++;
            }

            if ($onProgress !== null && $handled % self::PROGRESS_INTERVAL === 0) {
                $onProgress($handled, $total);
            }
        }

        if ($onProgress !== null) {
            $onProgress($handled, $total);
        }

        return $updatedCount;
    }

    /**
     * Read the description templates of our spells, and then of the spells those reference, until no
     * new spell is pulled in - descriptions quote each other several levels deep.
     *
     * @param array<int, bool> $ourIds
     */
    private function readDescriptionTemplates(string $build, array $ourIds): SpellDescriptionTemplates
    {
        $described     = [];
        $presentIds    = [];
        $referencedIds = [];
        $wantedIds     = $ourIds;

        for ($pass = 0; $pass < self::MAX_REFERENCE_PASSES && $wantedIds !== []; $pass++) {
            $newlyDescribed = [];

            foreach ($this->wagoToolsService->readTable('Spell', $build) as $row) {
                $spellId = (int)($row['ID'] ?? 0);

                // Recorded for every row, so we can tell "this build dropped the description" apart from
                // "this build has never heard of the spell"
                $presentIds[$spellId] = true;

                if (!isset($wantedIds[$spellId]) || isset($described[$spellId]) || trim($row['Description_lang'] ?? '') === '') {
                    continue;
                }

                $newlyDescribed[$spellId] = $row['Description_lang'];
            }

            $described += $newlyDescribed;

            // Whatever those templates point at in turn, minus everything we already hold. A referenced
            // spell without a description of its own still has values and a name to look up.
            $referencedIds += $this->collectReferencedSpellIds($newlyDescribed);
            $wantedIds = array_diff_key($referencedIds, $described);
        }

        return new SpellDescriptionTemplates($described, $presentIds, $referencedIds);
    }

    /**
     * Every spell id a set of templates refers to, either for a value (`$319949s1`) or for a whole
     * description (`$@spelldesc2823`).
     *
     * @param  array<int, string> $templates
     * @return array<int, bool>
     */
    private function collectReferencedSpellIds(array $templates): array
    {
        $referencedIds = [];

        foreach ($templates as $template) {
            if (preg_match_all('/\$(?:(\d+)[a-zA-Z]|@[a-zA-Z]+(\d+))/', $template, $matches) === false) {
                continue;
            }

            foreach (array_merge($matches[1], $matches[2]) as $spellId) {
                if ($spellId !== '') {
                    $referencedIds[(int)$spellId] = true;
                }
            }
        }

        return $referencedIds;
    }

    /**
     * @param  array<int, bool>                        $wantedIds
     * @return array<int, array<int, SpellEffectData>>
     */
    private function readEffects(string $build, array $wantedIds): array
    {
        $radii = $this->readRadii($build);

        $effects      = [];
        $difficulties = [];

        foreach ($this->wagoToolsService->readTable('SpellEffect', $build) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (!isset($wantedIds[$spellId])) {
                continue;
            }

            $effectIndex = (int)($row['EffectIndex'] ?? 0);
            $difficulty  = (int)($row['DifficultyID'] ?? 0);

            // Effects exist per difficulty; the base difficulty is what a description is written for, and
            // anything else only fills in for a spell that has no base row at all
            if (isset($difficulties[$spellId][$effectIndex]) && ($difficulty !== 0 || $difficulties[$spellId][$effectIndex] === 0)) {
                continue;
            }

            $radiusIndex    = (int)($row['EffectRadiusIndex_0'] ?? 0);
            $maxRadiusIndex = (int)($row['EffectRadiusIndex_1'] ?? 0);

            $effects[$spellId][$effectIndex] = new SpellEffectData(
                effectType: (int)($row['Effect'] ?? 0),
                auraType: (int)($row['EffectAura'] ?? 0),
                basePoints: (float)($row['EffectBasePointsF'] ?? 0),
                variance: (float)($row['Variance'] ?? 0),
                periodMs: (int)($row['EffectAuraPeriod'] ?? 0),
                chainTargets: (int)($row['EffectChainTargets'] ?? 0),
                radius: $radii[$radiusIndex]['radius'] ?? null,
                maxRadius: $radii[$maxRadiusIndex]['radius'] ?? $radii[$radiusIndex]['maxRadius'] ?? null,
            );

            $difficulties[$spellId][$effectIndex] = $difficulty;
        }

        return $effects;
    }

    /**
     * @return array<int, array{radius: float, maxRadius: float}>
     */
    private function readRadii(string $build): array
    {
        $radii = [];

        foreach ($this->wagoToolsService->readTable('SpellRadius', $build) as $row) {
            $radii[(int)($row['ID'] ?? 0)] = [
                'radius'    => (float)($row['Radius'] ?? 0),
                'maxRadius' => (float)($row['RadiusMax'] ?? 0),
            ];
        }

        return $radii;
    }

    /**
     * @param  array<int, bool> $wantedIds
     * @return array<int, int>
     */
    private function readDurations(string $build, array $wantedIds): array
    {
        $durationsByIndex = [];

        foreach ($this->wagoToolsService->readTable('SpellDuration', $build) as $row) {
            $durationsByIndex[(int)($row['ID'] ?? 0)] = (int)($row['Duration'] ?? 0);
        }

        $durations    = [];
        $difficulties = [];

        foreach ($this->wagoToolsService->readTable('SpellMisc', $build) as $row) {
            $spellId = (int)($row['SpellID'] ?? 0);

            if (!isset($wantedIds[$spellId])) {
                continue;
            }

            $difficulty = (int)($row['DifficultyID'] ?? 0);

            if (isset($difficulties[$spellId]) && ($difficulty !== 0 || $difficulties[$spellId] === 0)) {
                continue;
            }

            $durationIndex = (int)($row['DurationIndex'] ?? 0);

            if (!isset($durationsByIndex[$durationIndex])) {
                continue;
            }

            $durations[$spellId]    = $durationsByIndex[$durationIndex];
            $difficulties[$spellId] = $difficulty;
        }

        return $durations;
    }

    /**
     * @param  array<int, bool>   $wantedIds
     * @return array<int, string>
     */
    private function readNames(string $build, array $wantedIds): array
    {
        $names = [];

        foreach ($this->wagoToolsService->readTable('SpellName', $build) as $row) {
            $spellId = (int)($row['ID'] ?? 0);

            if (!isset($wantedIds[$spellId])) {
                continue;
            }

            $names[$spellId] = $row['Name_lang'] ?? '';
        }

        return $names;
    }

    /**
     * The named variables a description may use, e.g. `$mult=${$m2/100}`, resolved from the variable set a
     * spell is linked to.
     *
     * @return array<int, array<string, string>>
     */
    private function readDescriptionVariables(string $build): array
    {
        $variableSets = [];

        foreach ($this->wagoToolsService->readTable('SpellDescriptionVariables', $build) as $row) {
            $variables = [];

            foreach (preg_split('/\r\n|\n|\r/', $row['Variables'] ?? '') ?: [] as $line) {
                if (preg_match('/^\$(\w+)=(.*)$/', trim($line), $matches) === 1) {
                    $variables[$matches[1]] = $matches[2];
                }
            }

            $variableSets[(int)($row['ID'] ?? 0)] = $variables;
        }

        $descriptionVariables = [];

        foreach ($this->wagoToolsService->readTable('SpellXDescriptionVariables', $build) as $row) {
            $variableSetId = (int)($row['SpellDescriptionVariablesID'] ?? 0);

            if (isset($variableSets[$variableSetId])) {
                $descriptionVariables[(int)($row['SpellID'] ?? 0)] = $variableSets[$variableSetId];
            }
        }

        return $descriptionVariables;
    }
}
