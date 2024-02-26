<?php

namespace Tests\TestCases;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class PublicTestCase extends TestCase
{
    /**
     * @param string $originalClassName
     * @return MockObject
     */
    public function createMock(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    /**
     * @param string $originalClassName
     * @param array $methods
     * @return MockObject
     */
    public function createPartialMock(string $originalClassName, array $methods): MockObject
    {
        return parent::createPartialMock($originalClassName, $methods);
    }

}
