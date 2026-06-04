<?php

namespace Tests\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Repeat
{
    public function __construct(public readonly int $times) {}
}
