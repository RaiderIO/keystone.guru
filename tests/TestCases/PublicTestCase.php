<?php

namespace Tests\TestCases;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class PublicTestCase extends TestCase
{
    public function createMock(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    public function createPartialMock(string $originalClassName, array $methods): MockObject
    {
        return parent::createPartialMock($originalClassName, $methods);
    }
}
