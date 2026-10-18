<?php

namespace Tests\Feature\View\Common\Maps;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonDifficulty')]
final class MapDungeonDifficultySwitchTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string SWITCH_ID        = 'id="map_dungeon_difficulty_dropdown"';
    private const string ENEMY_VISUALS_ID = 'id="map_enemy_visuals_dropdown"';

    #[Test]
    public function explore_givenADungeonWithDifficultySpecificEnemies_rendersTheRaidSizeSwitchOn10Man(): void
    {
        // Arrange
        [, $url] = $this->exploreUrl(true);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertOk();
        $xpath = $this->parse((string)$response->getContent());
        $this->assertSame(['1', '2'], $this->attributeValues($xpath, '//*[@id="map_dungeon_difficulty_dropdown"]/a[@data-value]', 'data-value'));
        $this->assertSame(
            ['1'],
            $this->attributeValues($xpath, '//*[@id="map_dungeon_difficulty_dropdown"]/a[contains(concat(" ", normalize-space(@class), " "), " active ")]', 'data-value'),
        );
    }

    #[Test]
    public function explore_givenADungeonWithoutDifficultySpecificEnemies_omitsTheRaidSizeSwitch(): void
    {
        // Arrange
        [, $url] = $this->exploreUrl(false);

        // Act
        $response = $this->get($url);

        // Assert - the rail itself did render
        $response->assertOk();
        $response->assertSee(self::ENEMY_VISUALS_ID, false);
        $response->assertDontSee(self::SWITCH_ID, false);
    }

    #[Test]
    public function explore_givenADungeonWithAllDifficultiesEnabled_rendersEveryDifficulty(): void
    {
        // Arrange
        [$dungeon, $url]     = $this->exploreUrl(true);
        $enabledDifficulties = $dungeon->getEnabledSpeedrunDifficulties();
        $addedDifficulties   = collect(DungeonDifficulty::cases())
            ->reject(static fn(DungeonDifficulty $dungeonDifficulty) => in_array($dungeonDifficulty->value, $enabledDifficulties, true))
            ->map(static fn(DungeonDifficulty $dungeonDifficulty) => DungeonSpeedrunDifficulty::query()->create([
                'dungeon_id' => $dungeon->id,
                'difficulty' => $dungeonDifficulty->value,
            ]));
        $this->assertNotEmpty($addedDifficulties, 'Expected a dungeon that does not have every difficulty enabled yet');

        try {
            // Act
            $response = $this->get($url);

            // Assert
            $response->assertOk();
            $xpath = $this->parse((string)$response->getContent());
            $this->assertSame(
                array_map(strval(...), DungeonDifficulty::values()),
                $this->attributeValues($xpath, '//*[@id="map_dungeon_difficulty_dropdown"]/a[@data-value]', 'data-value'),
            );
        } finally {
            DungeonSpeedrunDifficulty::query()->whereKey($addedDifficulties->pluck('id')->all())->delete();
        }
    }

    #[Test]
    public function explore_givenADungeonWithOnlyOneDifficultyEnabled_omitsTheRaidSizeSwitch(): void
    {
        // Arrange
        [$dungeon, $url] = $this->exploreUrl(true);
        /** @var list<array<string, mixed>> $removedRows */
        $removedRows = DungeonSpeedrunDifficulty::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('difficulty', '!=', DungeonDifficulty::TWENTY_FIVE_MAN->value)
            ->get()
            ->map(static fn(DungeonSpeedrunDifficulty $row) => $row->getAttributes())
            ->all();
        $this->assertNotEmpty($removedRows, 'Expected a dungeon with more than one difficulty enabled');

        try {
            DungeonSpeedrunDifficulty::query()->whereKey(array_column($removedRows, 'id'))->delete();
            $this->assertSame([DungeonDifficulty::TWENTY_FIVE_MAN->value], $dungeon->load('dungeonSpeedrunDifficulties')->getEnabledSpeedrunDifficulties());

            // Act
            $response = $this->get($url);

            // Assert - the rail itself did render
            $response->assertOk();
            $response->assertSee(self::ENEMY_VISUALS_ID, false);
            $response->assertDontSee(self::SWITCH_ID, false);
        } finally {
            DungeonSpeedrunDifficulty::query()->insert($removedRows);
        }
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument();
        @$document->loadHTML(sprintf('<?xml encoding="UTF-8">%s', $html));

        return new DOMXPath($document);
    }

    /**
     * @return list<string>
     */
    private function attributeValues(DOMXPath $xpath, string $query, string $attribute): array
    {
        $values = [];
        /** @var DOMElement $element */
        foreach ($xpath->query($query) as $element) {
            $values[] = $element->getAttribute($attribute);
        }

        return $values;
    }

    /**
     * @return array{Dungeon, string}
     */
    private function exploreUrl(bool $withDifficultySpecificEnemies): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            dungeonActive:       true,
            requireDefaultFloor: true,
            resolve:             static fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?bool => $mappingVersion->enemies()
                ->whereNotNull('dungeon_difficulty')
                ->exists() === $withDifficultySpecificEnemies ? true : null,
        );
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        return [
            $dungeon,
            route('dungeon.explore.gameversion.view.floor', [
                'gameVersion' => $mappingVersion->gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $floor->index,
            ]),
        ];
    }
}
