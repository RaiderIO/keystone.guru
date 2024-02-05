<?php

namespace Tests\TestCases;

use App\User;

class AjaxPublicTestCase extends PublicTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));

        $this->defaultHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }
}
