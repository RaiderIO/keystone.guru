<?php

namespace Tests\Feature\App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\EnemyPack;
use App\Models\MountableArea;
use App\Models\Polyline;
use App\SeederHelpers\RelationImport\Parsers\Relation\MountableAreaPolylineRelationParser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeederHelpers')]
final class MountableAreaPolylineRelationParserTest extends PublicTestCase
{
    #[Test]
    public function parseRelation_givenMountableAreaWithPolyline_insertsThePolylineLinksItAndMirrorsItsVertices(): void
    {
        // Arrange
        $parser = new MountableAreaPolylineRelationParser();
        // An id no seeded mountable area has - the parser only records it on the polyline
        $mountableAreaId = (int)MountableArea::query()->max('id') + 1000;

        /** @var array<string, mixed> $value */
        $value = json_decode('{"color": "#eb4934", "color_animated": null, "weight": 1, "vertices_json": "[{\"lat\":-10,\"lng\":10},{\"lat\":-20,\"lng\":20}]"}', true);

        try {
            // Act
            $result = $parser->parseRelation(MountableArea::class, ['id' => $mountableAreaId, 'speed' => 150], 'polyline', $value);

            // Assert
            $this->assertSame(150, $result['speed']);
            $this->assertSame('[{"lat":-10,"lng":10},{"lat":-20,"lng":20}]', $result['vertices_json']);

            /** @var Polyline $polyline */
            $polyline = Polyline::query()->findOrFail($result['polyline_id']);
            $this->assertSame(MountableArea::class, $polyline->model_class);
            $this->assertSame($mountableAreaId, $polyline->model_id);
            $this->assertSame('#eb4934', $polyline->color);
            $this->assertNull($polyline->color_animated);
            $this->assertSame(1, $polyline->weight);
            $this->assertSame('[{"lat":-10,"lng":10},{"lat":-20,"lng":20}]', $polyline->vertices_json);
        } finally {
            Polyline::query()->where('model_class', MountableArea::class)->where('model_id', $mountableAreaId)->delete();
        }
    }

    #[Test]
    public function canParseModel_givenAnotherPolylineOwner_returnsFalse(): void
    {
        // Arrange
        $parser = new MountableAreaPolylineRelationParser();

        // Act
        $canParseEnemyPack     = $parser->canParseModel(EnemyPack::class);
        $canParseMountableArea = $parser->canParseModel(MountableArea::class);

        // Assert
        $this->assertFalse($canParseEnemyPack);
        $this->assertTrue($canParseMountableArea);
    }

    #[Test]
    public function canParseRelation_givenOtherRelation_returnsFalse(): void
    {
        // Arrange
        $parser = new MountableAreaPolylineRelationParser();

        // Act
        $canParseEnemies  = $parser->canParseRelation('enemies', []);
        $canParsePolyline = $parser->canParseRelation('polyline', []);

        // Assert
        $this->assertFalse($canParseEnemies);
        $this->assertTrue($canParsePolyline);
    }
}
