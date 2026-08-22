<?php

namespace App\Service\Spell\Tuning;

use App\Models\Spell\Spell;
use App\Repositories\Interfaces\Spell\SpellDescriptionImportStateRepositoryInterface;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;
use App\Service\Spell\Tuning\Exceptions\SpellTuningSnapshotException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use JsonException;

class SpellTuningSnapshotLoader implements SpellTuningSnapshotLoaderInterface
{
    private const string SPELLS_REPOSITORY_PATH = 'database/seeders/dungeondata/spells.json';

    private const string IMPORT_STATE_REPOSITORY_PATH = 'database/data/spell_description/import_state.json';

    public function __construct(
        private readonly SpellDescriptionImportStateRepositoryInterface $spellDescriptionImportStateRepository,
    ) {
    }

    public function load(string $source, ?string $buildOverride, int $gameVersionId): SpellTuningSnapshot
    {
        if ($source === self::SOURCE_DATABASE) {
            return $this->loadFromDatabase($buildOverride, $gameVersionId);
        }

        if (File::isFile($source)) {
            return $this->loadFromFile($source, $buildOverride, $gameVersionId);
        }

        return $this->loadFromGitRef($source, $buildOverride, $gameVersionId);
    }

    private function loadFromDatabase(?string $buildOverride, int $gameVersionId): SpellTuningSnapshot
    {
        $build = $buildOverride ?? $this->spellDescriptionImportStateRepository->findLastImportedBuild($gameVersionId);
        if ($build === null) {
            throw new SpellTuningSnapshotException(sprintf(
                'No spell description import has been recorded for game version %d - pass the build explicitly.',
                $gameVersionId,
            ));
        }

        $spells = Spell::query()
            ->where('game_version_id', $gameVersionId)
            ->get(['id', 'game_version_id', 'description_format', 'description_values'])
            ->map(static fn(Spell $spell): array => [
                'id'                 => $spell->id,
                'game_version_id'    => $spell->game_version_id,
                'description_format' => $spell->description_format,
                'description_values' => $spell->description_values,
            ])
            ->all();

        return SpellTuningSnapshot::fromSpellArrays($build, $gameVersionId, $spells);
    }

    private function loadFromFile(string $path, ?string $buildOverride, int $gameVersionId): SpellTuningSnapshot
    {
        if ($buildOverride === null) {
            throw new SpellTuningSnapshotException(sprintf(
                'A spells.json file carries no build of its own - pass the build %s belongs to explicitly.',
                $path,
            ));
        }

        return SpellTuningSnapshot::fromSpellArrays(
            $buildOverride,
            $gameVersionId,
            $this->decodeSpells(File::get($path), $path),
        );
    }

    private function loadFromGitRef(string $ref, ?string $buildOverride, int $gameVersionId): SpellTuningSnapshot
    {
        $spellsJson = $this->gitShow($ref, self::SPELLS_REPOSITORY_PATH);
        if ($spellsJson === null) {
            throw new SpellTuningSnapshotException(sprintf(
                '%s is neither a readable file nor a git ref that has %s. Git refs only resolve from the main checkout\'s ' .
                'app container - a worktree\'s container cannot see the main checkout\'s .git. From a worktree, export the ' .
                'file on the host and pass it with its build: `git show %s:%s > storage/app/old.json`, then ' .
                '`--from=storage/app/old.json --from-build=<build of that commit>`.',
                $ref,
                self::SPELLS_REPOSITORY_PATH,
                $ref,
                self::SPELLS_REPOSITORY_PATH,
            ));
        }

        $build = $buildOverride ?? $this->findBuildInImportState($this->gitShow($ref, self::IMPORT_STATE_REPOSITORY_PATH), $gameVersionId);
        if ($build === null) {
            throw new SpellTuningSnapshotException(sprintf(
                '%s does not record which build game version %d was imported from (%s is missing or empty there) - pass the build explicitly.',
                $ref,
                $gameVersionId,
                self::IMPORT_STATE_REPOSITORY_PATH,
            ));
        }

        return SpellTuningSnapshot::fromSpellArrays(
            $build,
            $gameVersionId,
            $this->decodeSpells($spellsJson, sprintf('%s:%s', $ref, self::SPELLS_REPOSITORY_PATH)),
        );
    }

    /**
     * The contents of a file at a git ref of this repository, or null when git cannot produce it.
     */
    private function gitShow(string $ref, string $path): ?string
    {
        $result = Process::path(base_path())->run(['git', 'show', sprintf('%s:%s', $ref, $path)]);

        return $result->successful() ? $result->output() : null;
    }

    private function findBuildInImportState(?string $importStateJson, int $gameVersionId): ?string
    {
        if ($importStateJson === null) {
            return null;
        }

        try {
            $states = json_decode($importStateJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $build = $states[(string)$gameVersionId]['build'] ?? null;

        return is_string($build) && $build !== '' ? $build : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeSpells(string $json, string $describedSource): array
    {
        try {
            $spells = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SpellTuningSnapshotException(sprintf('%s is not valid JSON: %s', $describedSource, $exception->getMessage()), 0, $exception);
        }

        if (!is_array($spells)) {
            throw new SpellTuningSnapshotException(sprintf('%s does not contain a list of spells.', $describedSource));
        }

        return $spells;
    }
}
