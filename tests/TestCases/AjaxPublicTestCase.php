<?php

namespace Tests\TestCases;

use App\User;

final class AjaxPublicTestCase extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));

        $this->defaultHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }
}
