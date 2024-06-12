<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait APIAuthentication
{
    public function addAuthentication(): void
    {
        // Annoying is that this should be called Authentication, but it's not, ugh
        // $this->withHeader('Authorization', 'Basic ' . base64_encode('admin@app.com:password'));

        $this->be(User::findOrFail(1));
    }
}
