<?php

namespace Tests\Unit\Database\Seeders;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Characteristic;
use App\Models\CharacterRace;
use App\Models\CharacterRaceClassCoupling;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIconType;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcType;
use App\Models\Patreon\PatreonBenefit;
use App\Models\PublishedState;
use App\Models\RaidMarker;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Models\SeasonDungeon;
use App\Models\Spell\SpellEffect;
use App\Models\Tags\TagCategory;
use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Traits\SeederModel;
use App\Models\Translation\Translation;
use App\SeederHelpers\RelationImport\Mapping\RelationMapping;
use Database\Seeders\AffixSeeder;
use Database\Seeders\CharacterClassesSeeder;
use Database\Seeders\CharacterClassSpecializationsSeeder;
use Database\Seeders\CharacteristicsSeeder;
use Database\Seeders\CharacterRaceClassesSeeder;
use Database\Seeders\CharacterRacesSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DungeonDataSeeder;
use Database\Seeders\DungeonRouteCollectionCategoriesSeeder;
use Database\Seeders\ExpansionsSeeder;
use Database\Seeders\FactionsSeeder;
use Database\Seeders\GameServerRegionsSeeder;
use Database\Seeders\GameVersionsSeeder;
use Database\Seeders\MapIconTypesSeeder;
use Database\Seeders\NpcClassesSeeder;
use Database\Seeders\NpcClassificationsSeeder;
use Database\Seeders\NpcTypesSeeder;
use Database\Seeders\PatreonBenefitsSeeder;
use Database\Seeders\PublishedStatesSeeder;
use Database\Seeders\RaidMarkersSeeder;
use Database\Seeders\RouteAttributesSeeder;
use Database\Seeders\SeasonsSeeder;
use Database\Seeders\TableSeederInterface;
use Database\Seeders\TagCategorySeeder;
use Database\Seeders\TimewalkingEventSeeder;
use Database\Seeders\TranslationsSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DungeonDataSeederTest extends TestCase
{
    /**
     * SeederModels whose rows another seeder owns, keyed by model, valued by that seeder. DungeonDataSeeder must not
     * list them: it would rebuild their tables from the dungeon JSON files, which do not hold them.
     *
     * @var array<class-string, class-string<TableSeederInterface>>
     */
    private const array SEEDED_BY_ANOTHER_SEEDER = [
        Affix::class                          => AffixSeeder::class,
        AffixGroup::class                     => AffixSeeder::class,
        AffixGroupCoupling::class             => AffixSeeder::class,
        CharacterClass::class                 => CharacterClassesSeeder::class,
        CharacterClassSpecialization::class   => CharacterClassSpecializationsSeeder::class,
        Characteristic::class                 => CharacteristicsSeeder::class,
        CharacterRace::class                  => CharacterRacesSeeder::class,
        CharacterRaceClassCoupling::class     => CharacterRaceClassesSeeder::class,
        DungeonRouteCollectionCategory::class => DungeonRouteCollectionCategoriesSeeder::class,
        Expansion::class                      => ExpansionsSeeder::class,
        Faction::class                        => FactionsSeeder::class,
        GameServerRegion::class               => GameServerRegionsSeeder::class,
        GameVersion::class                    => GameVersionsSeeder::class,
        MapIconType::class                    => MapIconTypesSeeder::class,
        NpcClass::class                       => NpcClassesSeeder::class,
        NpcClassification::class              => NpcClassificationsSeeder::class,
        NpcType::class                        => NpcTypesSeeder::class,
        PatreonBenefit::class                 => PatreonBenefitsSeeder::class,
        PublishedState::class                 => PublishedStatesSeeder::class,
        RaidMarker::class                     => RaidMarkersSeeder::class,
        RouteAttribute::class                 => RouteAttributesSeeder::class,
        Season::class                         => SeasonsSeeder::class,
        SeasonDungeon::class                  => SeasonsSeeder::class,
        TagCategory::class                    => TagCategorySeeder::class,
        TimewalkingEvent::class               => TimewalkingEventSeeder::class,
        Translation::class                    => TranslationsSeeder::class,
    ];

    /**
     * SeederModels no seeder writes, keyed by model, valued by the reason.
     *
     * @var array<class-string, string>
     */
    private const array NOT_SEEDED = [
        MappingChangeLog::class => 'Written by the mapping editor (ChangesMapping) and read by mapping:restore; no seeder or dungeon JSON file holds its rows.',
    ];

    #[Test]
    public function getAffectedModelClasses_givenEveryRelationMapping_coversExactlyTheSeederModels(): void
    {
        // Arrange - DatabaseSeeder::getTempTableName() appends `_temp` for any model using the SeederModel
        // trait, and flushModels() inserts into that name. The temp table itself is only ever created for a
        // model listed in getAffectedModelClasses(), so a SeederModel that is registered as a RelationMapping
        // but missing from that list makes the seeder insert into a table that does not exist - the moment its
        // JSON file holds a single row. That is what happened to EnemyForcesCheckpoint (#3702).
        //
        // The reverse must hold too: a model that does NOT use SeederModel writes straight to its live table,
        // so listing it would build a temp table that is renamed over the live data while it is still empty.
        $relationMappingsProperty = new ReflectionProperty(DungeonDataSeeder::class, 'relationMapping');

        /** @var array<int, RelationMapping> $relationMappings */
        $relationMappings     = $relationMappingsProperty->getValue(new DungeonDataSeeder());
        $affectedModelClasses = DungeonDataSeeder::getAffectedModelClasses();

        $this->assertNotEmpty($relationMappings, 'DungeonDataSeeder should register relation mappings.');

        foreach ($relationMappings as $relationMapping) {
            $class = $relationMapping->getClass();

            // Act
            $usesTempTable = str_ends_with(DatabaseSeeder::getTempTableName($class), DatabaseSeeder::TEMP_TABLE_SUFFIX);

            // Assert
            $this->assertSame(
                $usesTempTable,
                in_array($class, $affectedModelClasses, true),
                sprintf(
                    '%s %s the %s trait, so it %s be listed in DungeonDataSeeder::getAffectedModelClasses().',
                    $class,
                    $usesTempTable ? 'uses' : 'does not use',
                    class_basename(SeederModel::class),
                    $usesTempTable ? 'must' : 'must not',
                ),
            );
        }
    }

    #[Test]
    public function getAffectedModelClasses_givenEverySeederModelInAppModels_listsAllButTheExcludedOnes(): void
    {
        // Arrange
        $seederModelClasses = $this->findSeederModelClasses();
        $excludedClasses    = array_merge(array_keys(self::SEEDED_BY_ANOTHER_SEEDER), array_keys(self::NOT_SEEDED));

        // Act
        $affectedModelClasses = DungeonDataSeeder::getAffectedModelClasses();

        // Assert
        $this->assertContains(SpellEffect::class, $seederModelClasses, 'The app/Models scan must find SeederModels nested in a subfolder.');
        $missingClasses = array_values(array_filter(
            $seederModelClasses,
            static fn(string $class): bool => !in_array($class, $affectedModelClasses, true) && !in_array($class, $excludedClasses, true),
        ));
        $this->assertSame([], $missingClasses, sprintf(
            'These SeederModels are neither in DungeonDataSeeder::getAffectedModelClasses() nor excluded in %s with a reason.',
            self::class,
        ));
    }

    #[Test]
    public function getAffectedModelClasses_givenExcludedSeederModels_listsNoneOfThemAndTheirOwnSeederDoes(): void
    {
        // Arrange
        $seederModelClasses = $this->findSeederModelClasses();

        // Act
        $affectedModelClasses = DungeonDataSeeder::getAffectedModelClasses();

        // Assert
        foreach (self::SEEDED_BY_ANOTHER_SEEDER as $class => $seederClass) {
            $this->assertContains($class, $seederModelClasses, sprintf('%s is excluded but does not use the SeederModel trait.', $class));
            $this->assertNotContains($class, $affectedModelClasses, sprintf('%s is excluded but DungeonDataSeeder lists it.', $class));
            $this->assertContains($class, $seederClass::getAffectedModelClasses(), sprintf('%s does not list %s.', $seederClass, $class));
        }

        foreach (array_keys(self::NOT_SEEDED) as $class) {
            $this->assertContains($class, $seederModelClasses, sprintf('%s is excluded but does not use the SeederModel trait.', $class));
            $this->assertNotContains($class, $affectedModelClasses, sprintf('%s is excluded but DungeonDataSeeder lists it.', $class));
        }
    }

    /**
     * @return array<int, class-string>
     */
    private function findSeederModelClasses(): array
    {
        $result = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = sprintf(
                'App\\Models\\%s',
                str_replace('/', '\\', Str::beforeLast($file->getRelativePathname(), '.php')),
            );

            if (!class_exists($class) || (new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            if (in_array(SeederModel::class, class_uses_recursive($class), true)) {
                $result[] = $class;
            }
        }

        sort($result);

        return $result;
    }
}
