<?php

namespace Tests\TestCases;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

abstract class PublicTestCase extends TestCase
{
    /**
     * @template T of object
     * @param  class-string<T> $originalClassName
     * @return MockObject&T
     */
    public function createMockPublic(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    /**
     * @template T of object
     * @param  class-string<T>    $originalClassName
     * @param  array<int, string> $methods
     * @return MockObject&T
     */
    public function createPartialMockPublic(string $originalClassName, array $methods): MockObject
    {
        return parent::createPartialMock($originalClassName, $methods);
    }

    /**
     * @template T of object
     * @param  class-string<T> $originalClassName
     * @return MockBuilder<T>
     */
    public function getMockBuilderPublic(string $originalClassName): MockBuilder
    {
        return parent::getMockBuilder($originalClassName);
    }
}
