<?php

namespace App\Service\Spell\Tuning;

use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;
use App\Service\Spell\Tuning\Exceptions\SpellTuningSnapshotException;

interface SpellTuningSnapshotLoaderInterface
{
    /** The source that reads the live `spells` table rather than a file. */
    public const string SOURCE_DATABASE = 'db';

    /**
     * Turns a source into a snapshot of one game version's spells. $source is one of:
     *
     * - `db` - the live spells table; the build comes from `spell_description_import_states`
     * - a file path to a spells.json; the build must be passed as $buildOverride
     * - a git ref (`HEAD`, a sha, a branch) - `spells.json` and `import_state.json` are read from that
     *   commit of this repository; the build comes from the latter unless $buildOverride is given
     *
     * A file path wins over a git ref when both would match, so a file is never mistaken for a ref.
     *
     * @throws SpellTuningSnapshotException when the source cannot be read or its build cannot be determined
     */
    public function load(string $source, ?string $buildOverride, int $gameVersionId): SpellTuningSnapshot;
}
