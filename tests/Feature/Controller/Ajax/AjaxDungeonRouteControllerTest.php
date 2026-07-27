<?php

namespace Tests\Feature\Controller\Ajax;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function get_givenMissingColumnsParameter_returnsUnprocessableEntity(): void
    {
        // Arrange - no columns parameter in the request

        // Act
        $response = $this->get('/ajax/routes');

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function get_givenTagsParameterIsTheStringUndefined_returnsOk(): void
    {
        // Arrange - this mirrors the request sent by a DungeonrouteTable instance whose tags
        // select isn't rendered for its view (e.g. the team edit page's Route Publishing tab),
        // where jQuery's `.val()` on the missing element resolves to `undefined`
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
            'tags'   => 'undefined',
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/routes?%s', $query));

        // Assert
        $response->assertOk();
    }
}
