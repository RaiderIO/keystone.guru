<?php

namespace Tests\Fixtures\Traits;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use Tests\TestCase;

/**
 * A spell of the test's own, with no observations, tuning changes or NPC assignments - the state a test asserting
 * an empty result needs, which no seeded spell promises to be in.
 *
 * Everything created here is deleted again when the test's application is torn down.
 *
 * @mixin TestCase
 */
trait CreatesSpell
{
    /** @var array<int, Spell> */
    private array $createdSpells = [];

    /**
     * @param array<string, mixed> $attributes Any `spells` column.
     */
    protected function createSpell(array $attributes = []): Spell
    {
        if ($this->createdSpells === []) {
            $this->beforeApplicationDestroyed(fn() => $this->deleteCreatedSpells());
        }

        $spell = Spell::create(array_merge([
            'id'              => max(900_000_000, (int)Spell::query()->max('id') + 1),
            'game_version_id' => GameVersion::getDefaultGameVersion()->id,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => 'Test Spell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => now(),
        ], $attributes));

        $this->createdSpells[] = $spell;

        return $spell;
    }

    /**
     * Runs on teardown by itself; call it directly only when the spell must be gone before the test ends.
     */
    protected function deleteCreatedSpells(): void
    {
        foreach ($this->createdSpells as $spell) {
            $spell->delete();
        }

        $this->createdSpells = [];
    }
}
