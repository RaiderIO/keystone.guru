<?php

namespace Tests\Feature\App\Service\PathFinding;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathEdge;
use App\Logic\Structs\PathNode;
use App\Service\PathFinding\PathFindingService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('PathFinding')]
final class PathFindingServiceTest extends PublicTestCase
{
    private PathFindingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PathFindingService();
    }

    private function makeNode(string $id, float $x = 0, float $y = 0): PathNode
    {
        return new PathNode($id, new LatLng(0, 0), new IngameXY($x, $y));
    }

    #[Test]
    public function findShortestPath_givenStartEqualsEnd_returnsSinglePoint(): void
    {
        // Arrange
        $nodes = ['a' => $this->makeNode('a')];

        // Act
        $result = $this->service->findShortestPath($nodes, [], 'a', 'a');

        // Assert
        $this->assertCount(1, $result);
    }

    #[Test]
    public function findShortestPath_givenDirectEdge_returnsDirectPath(): void
    {
        // Arrange
        $nodes = [
            'a' => $this->makeNode('a', 0, 0),
            'b' => $this->makeNode('b', 10, 0),
        ];
        $edges = [new PathEdge('a', 'b', 10), new PathEdge('b', 'a', 10)];

        // Act
        $result = $this->service->findShortestPath($nodes, $edges, 'a', 'b');

        // Assert
        $this->assertCount(2, $result);
    }

    #[Test]
    public function findShortestPath_givenTwoRoutes_returnsShortestOne(): void
    {
        // Arrange -- direct a->b (weight 100) vs indirect a->c->b (weight 3+3=6)
        $nodes = [
            'a' => $this->makeNode('a'),
            'b' => $this->makeNode('b'),
            'c' => $this->makeNode('c'),
        ];
        $edges = [
            new PathEdge('a', 'b', 100),
            new PathEdge('a', 'c', 3),
            new PathEdge('c', 'b', 3),
        ];

        // Act
        $result = $this->service->findShortestPath($nodes, $edges, 'a', 'b');

        // Assert -- shortest path goes through c (3 nodes)
        $this->assertCount(3, $result);
    }

    #[Test]
    public function findShortestPath_givenMultiHopChain_returnsAllWaypoints(): void
    {
        // Arrange -- a->b->c->d, each edge weight 1
        $nodes = [
            'a' => $this->makeNode('a'),
            'b' => $this->makeNode('b'),
            'c' => $this->makeNode('c'),
            'd' => $this->makeNode('d'),
        ];
        $edges = [
            new PathEdge('a', 'b', 1),
            new PathEdge('b', 'c', 1),
            new PathEdge('c', 'd', 1),
        ];

        // Act
        $result = $this->service->findShortestPath($nodes, $edges, 'a', 'd');

        // Assert
        $this->assertCount(4, $result);
    }

    #[Test]
    public function findShortestPath_givenNoPath_returnsEmpty(): void
    {
        // Arrange -- a and b exist but have no connecting edges
        $nodes = [
            'a' => $this->makeNode('a'),
            'b' => $this->makeNode('b'),
        ];

        // Act
        $result = $this->service->findShortestPath($nodes, [], 'a', 'b');

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function findShortestPath_givenUnknownStartNode_returnsEmpty(): void
    {
        // Arrange
        $nodes = ['a' => $this->makeNode('a')];

        // Act
        $result = $this->service->findShortestPath($nodes, [], 'unknown', 'a');

        // Assert
        $this->assertEmpty($result);
    }
}
