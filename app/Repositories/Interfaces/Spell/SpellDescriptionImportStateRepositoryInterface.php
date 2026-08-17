<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellDescriptionImportState;
use App\Repositories\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @method SpellDescriptionImportState                  create(array<string, mixed> $attributes)
 * @method SpellDescriptionImportState|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDescriptionImportState                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDescriptionImportState                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                         save(SpellDescriptionImportState $model)
 * @method bool                                         update(SpellDescriptionImportState $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                         delete(SpellDescriptionImportState $model)
 * @method Collection<int, SpellDescriptionImportState> all()
 * @method bool                                         exists(array<int, string> $columns)
 */
interface SpellDescriptionImportStateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The build most recently imported for the given game version, or null when none has been recorded
     * yet (e.g. before the first import since this table was introduced).
     */
    public function findLastImportedBuild(int $gameVersionId): ?string;

    /**
     * Records that $build was just imported for $gameVersionId - overwrites whatever was recorded
     * before, since only the most recent import matters for the patch check.
     */
    public function recordImport(int $gameVersionId, string $product, string $build, CarbonInterface $importedAt): void;
}
