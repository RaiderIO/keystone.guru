<?php

namespace Tests\TestCases;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

abstract class PublicTestCase extends TestCase
{
    public function createMockPublic(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    public function createPartialMockPublic(string $originalClassName, array $methods): MockObject
    {
        return parent::createPartialMock($originalClassName, $methods);
    }

    public function getMockBuilderPublic(string $originalClassName): MockBuilder
    {
        return parent::getMockBuilder($originalClassName);
    }
}
