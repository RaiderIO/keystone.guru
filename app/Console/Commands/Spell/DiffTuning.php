<?php

namespace App\Console\Commands\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellTuningChangeType;
use App\Service\Spell\Tuning\Dtos\SpellTuningChangeDto;
use App\Service\Spell\Tuning\Dtos\SpellTuningDiffResult;
use App\Service\Spell\Tuning\Exceptions\SpellTuningSnapshotException;
use App\Service\Spell\Tuning\SpellTuningDiffServiceInterface;
use App\Service\Spell\Tuning\SpellTuningSnapshotLoaderInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DiffTuning extends Command
{
    /** How many of the biggest damage/healing swings the summary lists before it stops. */
    private const int SUMMARY_ROW_LIMIT = 25;

    private const int SUMMARY_TEXT_LIMIT = 60;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spell:difftuning
                            {--from=HEAD : The previous build - a spells.json path, a git ref, or "db"}
                            {--to=db : The new build - "db", a spells.json path, or a git ref}
                            {--from-build= : Build of --from, when it cannot be read from import_state.json}
                            {--to-build= : Build of --to, when it cannot be read from import_state.json}
                            {--gameVersion=retail : Key of the game version to diff}
                            {--dry-run : Print the changes without storing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds the spell description numbers that changed between two client builds and records them as spell tuning changes.';

    public function handle(
        SpellTuningSnapshotLoaderInterface $spellTuningSnapshotLoader,
        SpellTuningDiffServiceInterface    $spellTuningDiffService,
    ): int {
        $gameVersionKey = (string)$this->option('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);
        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version %s', $gameVersionKey));

            return self::FAILURE;
        }

        $fromSource = (string)$this->option('from');
        $toSource   = (string)$this->option('to');

        try {
            $from = $spellTuningSnapshotLoader->load($fromSource, $this->optionOrNull('from-build'), $gameVersion->id);
            $to   = $spellTuningSnapshotLoader->load($toSource, $this->optionOrNull('to-build'), $gameVersion->id);
        } catch (SpellTuningSnapshotException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($from->build === $to->build) {
            $this->error(sprintf(
                'Both %s and %s are build %s - nothing to diff. Did the previous build already get committed? Pass --from=<ref|file> explicitly.',
                $fromSource,
                $toSource,
                $from->build,
            ));

            return self::FAILURE;
        }

        $this->info(sprintf('Comparing %s (%s) with %s (%s)...', $fromSource, $from->build, $toSource, $to->build));

        $result = $spellTuningDiffService->diff($from, $to);

        $this->printSummary($result);

        if ($this->option('dry-run')) {
            $this->comment('Dry run - nothing was stored.');

            return self::SUCCESS;
        }

        $stored = $spellTuningDiffService->store($result);

        $this->info(sprintf('Stored %d changes for build %s. Run mapping:save to write them to database/seeders/dungeondata/spell_tuning_changes.json.', $stored, $to->build));

        return self::SUCCESS;
    }

    private function optionOrNull(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function printSummary(SpellTuningDiffResult $result): void
    {
        $this->info(sprintf(
            '%d of %d compared spells changed between %s and %s (%d changes, of which %d rewritten descriptions).',
            $result->getChangedSpellCount(),
            $result->comparedSpellCount,
            $result->fromBuild,
            $result->toBuild,
            count($result->changes),
            $result->getRewrittenCount(),
        ));

        if ($result->changes === []) {
            return;
        }

        $spellNames = Spell::query()
            ->whereIn('id', array_unique(array_map(static fn(SpellTuningChangeDto $change): int => $change->spellId, $result->changes)))
            ->pluck('name', 'id');

        $scalable = array_filter($result->changes, static fn(SpellTuningChangeDto $change): bool => $change->isScalable());
        $other    = array_filter($result->changes, static fn(SpellTuningChangeDto $change): bool => !$change->isScalable());

        // Biggest swings first, unknown swings (a coefficient that appeared or disappeared) last
        usort($scalable, static fn(SpellTuningChangeDto $a, SpellTuningChangeDto $b): int => abs($b->delta ?? -INF) <=> abs($a->delta ?? -INF));

        $rows = [];
        foreach (array_slice($scalable, 0, self::SUMMARY_ROW_LIMIT) as $change) {
            $rows[] = $this->describeChange($change, $spellNames->get($change->spellId));
        }

        foreach ($other as $change) {
            $rows[] = $this->describeChange($change, $spellNames->get($change->spellId));
        }

        $this->table(['Spell', 'Kind', 'Old', 'New', 'Change'], $rows);

        if (count($scalable) > self::SUMMARY_ROW_LIMIT) {
            $this->comment(sprintf('... and %d more damage/healing changes not listed.', count($scalable) - self::SUMMARY_ROW_LIMIT));
        }
    }

    /**
     * @return array<int, string>
     */
    private function describeChange(SpellTuningChangeDto $change, ?string $nameKey): array
    {
        $spell = sprintf('%d %s', $change->spellId, $nameKey === null ? '' : __($nameKey));

        if ($change->changeType === SpellTuningChangeType::DescriptionRewritten) {
            return [
                $spell,
                'description',
                Str::limit($change->oldText ?? '-', self::SUMMARY_TEXT_LIMIT),
                Str::limit($change->newText ?? '-', self::SUMMARY_TEXT_LIMIT),
                'rewritten',
            ];
        }

        if ($change->isScalable()) {
            $old    = ($change->oldText ?? '') !== '' ? $change->oldText : sprintf('coef %s', $change->oldCoefficient ?? '-');
            $new    = ($change->newText ?? '') !== '' ? $change->newText : sprintf('coef %s', $change->newCoefficient ?? '-');
            $effect = $change->delta === null ? '?' : sprintf('%+.1f%%', $change->delta * 100);

            return [$spell, $this->describeKind($change), $old, $new, $effect];
        }

        return [$spell, $this->describeKind($change), $change->oldText ?? '-', $change->newText ?? '-', ''];
    }

    private function describeKind(SpellTuningChangeDto $change): string
    {
        return $change->kind === null ? '-' : $change->kind->value;
    }
}
