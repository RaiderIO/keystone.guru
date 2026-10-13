<?php

namespace Tests\Feature\Console\Commands\Wowhead;

use App\Console\Commands\Wowhead\FetchMissingSpells;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDispelType;
use App\Service\Spell\SpellServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Wowhead')]
final class FetchMissingSpellsTest extends PublicTestCase
{
    private const int MISSING_SPELL_ID = 999999921;

    #[Test]
    public function handle_givenASpellOnlyKnownByItsId_createsItBeforeFetchingItsData(): void
    {
        try {
            // Arrange - Wowhead knowing nothing leaves the spell as the command created it
            $spellService = $this->createMockPublic(SpellServiceInterface::class);
            $spellService->method('getMissingSpellIds')->willReturn([self::MISSING_SPELL_ID]);
            $this->app->instance(SpellServiceInterface::class, $spellService);

            $wowheadService = $this->createMockPublic(WowheadServiceInterface::class);
            $wowheadService->method('getSpellData')->willReturn(null);
            $this->app->instance(WowheadServiceInterface::class, $wowheadService);

            // Act
            $this->artisan(FetchMissingSpells::class);

            // Assert
            $spell = Spell::query()->findOrFail(self::MISSING_SPELL_ID);
            $this->assertSame(SpellDispelType::Unknown->translationKey(), $spell->dispel_type);
            $this->assertSame('', $spell->name);
        } finally {
            Spell::query()->whereKey(self::MISSING_SPELL_ID)->delete();
        }
    }
}
