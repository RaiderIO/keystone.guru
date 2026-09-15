<?php

namespace Tests\Feature\Controller\Ajax;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('User')]
final class AjaxUserControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function get_givenColumnEntryWithNameOnly_returnsOk(): void
    {
        // Arrange - a columns[] entry that carries a name but neither 'searchable' nor 'orderable',
        // as seen in PHP-LARAVEL-S9 (#4438): a partial datatables columns payload from the client
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data' => 0,
                    'name' => 'name',
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/admin/user?%s', $query));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function get_givenOrderWithoutDirection_returnsOk(): void
    {
        // Arrange - an order[] entry that names a column but omits 'dir'
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'name',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => 0]],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/admin/user?%s', $query));

        // Assert
        $response->assertOk();
    }
}
