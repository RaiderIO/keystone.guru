<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellDescriptionImportState;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellDescriptionImportStateRepositoryInterface;
use Carbon\CarbonInterface;

class SpellDescriptionImportStateRepository extends DatabaseRepository implements SpellDescriptionImportStateRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SpellDescriptionImportState::class);
    }

    public function findLastImportedBuild(int $gameVersionId): ?string
    {
        /** @var SpellDescriptionImportState|null $state */
        $state = SpellDescriptionImportState::query()->find($gameVersionId, ['build']);

        return $state?->build;
    }

    public function recordImport(int $gameVersionId, string $product, string $build, CarbonInterface $importedAt): void
    {
        SpellDescriptionImportState::query()->updateOrCreate(
            ['game_version_id' => $gameVersionId],
            ['product' => $product, 'build' => $build, 'imported_at' => $importedAt],
        );
    }
}
