<?php

namespace Tests\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class Repeat
{
    public function __construct(public int $times)
    {
    }
}
