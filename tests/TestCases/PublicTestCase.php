<?php

namespace Tests\TestCases;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

abstract class PublicTestCase extends TestCase
{
    protected function createMock(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    protected function createPartialMock(string $originalClassName, array $methods): MockObject
    {
        return parent::createPartialMock($originalClassName, $methods);
    }
}
