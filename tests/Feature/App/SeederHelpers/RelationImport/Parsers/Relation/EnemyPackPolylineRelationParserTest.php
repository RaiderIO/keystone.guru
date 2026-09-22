<?php

namespace Tests\Feature\App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Polyline;
use App\SeederHelpers\RelationImport\Parsers\Relation\EnemyPackPolylineRelationParser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeederHelpers')]
final class EnemyPackPolylineRelationParserTest extends PublicTestCase
{
    #[Test]
    public function parseRelation_givenPackWithPolyline_insertsThePolylineAndLinksItToThePack(): void
    {
        // Arrange
        $parser = new EnemyPackPolylineRelationParser();
        // An id no seeded pack has - the parser only records it on the polyline
        $enemyPackId = (int)EnemyPack::query()->max('id') + 1000;

        /** @var array<string, mixed> $value */
        $value = json_decode('{"color": "#5993D2", "color_animated": null, "weight": 1, "vertices_json": "[{\"lat\":-10,\"lng\":10},{\"lat\":-20,\"lng\":20}]"}', true);

        try {
            // Act
            $result = $parser->parseRelation(EnemyPack::class, ['id' => $enemyPackId, 'label' => 'Enemy pack'], 'polyline', $value);

            // Assert
            $this->assertSame('Enemy pack', $result['label']);

            /** @var Polyline $polyline */
            $polyline = Polyline::query()->findOrFail($result['polyline_id']);
            $this->assertSame(EnemyPack::class, $polyline->model_class);
            $this->assertSame($enemyPackId, $polyline->model_id);
            $this->assertSame('#5993D2', $polyline->color);
            $this->assertNull($polyline->color_animated);
            $this->assertSame(1, $polyline->weight);
            $this->assertSame('[{"lat":-10,"lng":10},{"lat":-20,"lng":20}]', $polyline->vertices_json);
        } finally {
            Polyline::query()->where('model_class', EnemyPack::class)->where('model_id', $enemyPackId)->delete();
        }
    }

    #[Test]
    public function canParseModel_givenAnotherPolylineOwner_returnsFalse(): void
    {
        // Arrange
        $parser = new EnemyPackPolylineRelationParser();

        // Act
        $canParseEnemyPatrol = $parser->canParseModel(EnemyPatrol::class);
        $canParseEnemyPack   = $parser->canParseModel(EnemyPack::class);

        // Assert
        $this->assertFalse($canParseEnemyPatrol);
        $this->assertTrue($canParseEnemyPack);
    }

    #[Test]
    public function canParseRelation_givenOtherRelation_returnsFalse(): void
    {
        // Arrange
        $parser = new EnemyPackPolylineRelationParser();

        // Act
        $canParseEnemies  = $parser->canParseRelation('enemies', []);
        $canParsePolyline = $parser->canParseRelation('polyline', []);

        // Assert
        $this->assertFalse($canParseEnemies);
        $this->assertTrue($canParsePolyline);
    }
}
