<?php

namespace Tests\Fixtures\Traits;

use App\Models\Expansion;
use Tests\TestCase;

/**
 * An expansion of the test's own, inactive by default: the seed promises active expansions, not that a retired
 * one exists.
 *
 * Everything created here is deleted again when the test's application is torn down.
 *
 * @mixin TestCase
 */
trait CreatesExpansion
{
    /** @var array<int, Expansion> */
    private array $createdExpansions = [];

    /**
     * @param array<string, mixed> $attributes Any `expansions` column.
     */
    protected function createExpansion(array $attributes = []): Expansion
    {
        if ($this->createdExpansions === []) {
            $this->beforeApplicationDestroyed(fn() => $this->deleteCreatedExpansions());
        }

        $expansion = Expansion::create(array_merge([
            'name'        => 'Test Expansion',
            'shortname'   => sprintf('test_%s', uniqid()),
            'color'       => '#000000',
            'released_at' => now()->subYear()->toDateTimeString(),
            'active'      => false,
        ], $attributes));

        $this->createdExpansions[] = $expansion;

        return $expansion;
    }

    /**
     * Runs on teardown by itself; call it directly only when the expansion must be gone before the test ends.
     */
    protected function deleteCreatedExpansions(): void
    {
        foreach ($this->createdExpansions as $expansion) {
            $expansion->delete();
        }

        $this->createdExpansions = [];
    }
}
