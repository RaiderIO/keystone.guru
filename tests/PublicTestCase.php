<?php

namespace Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublicTestCase extends TestCase
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
