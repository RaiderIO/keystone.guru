<?php

namespace Tests\Fixtures;

use App\Service\Traits\Curl;

/**
 * The Curl trait is only ever used from a class; this is a minimal host for it so its behaviour can be tested
 * without dragging in one of the real consuming services.
 */
class CurlTraitConsumer
{
    use Curl;
}
