<?php

namespace Tests\Feature\View\Common\Maps;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
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
        $url = $this->exploreUrl(true);

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
        $url = $this->exploreUrl(false);

        // Act
        $response = $this->get($url);

        // Assert - the rail itself did render
        $response->assertOk();
        $response->assertSee(self::ENEMY_VISUALS_ID, false);
        $response->assertDontSee(self::SWITCH_ID, false);
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

    private function exploreUrl(bool $withDifficultySpecificEnemies): string
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

        return route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $mappingVersion->gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);
    }
}
