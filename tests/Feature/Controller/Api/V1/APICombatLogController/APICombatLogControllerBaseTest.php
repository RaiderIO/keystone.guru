<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\APIPublicTestCase;
use Tests\Traits\ValidatesUrls;

abstract class APICombatLogControllerBaseTest extends APIPublicTestCase
{
    use LoadsJsonFiles, ValidatesUrls;

    protected function validateResponseStaticData(array $response): void
    {
        // AffixGroups
        $this->assertNotEmpty($response['data']['affix_groups']);
        $this->assertNotEmpty($response['data']['affix_groups'][0]['affixes'][0]);

        // Author
        $this->assertEquals('Admin', $response['data']['author']['name']);
        $this->assertNotEmpty($response['data']['author']['links']);
        $this->assertTrue($this->isValidUrl($response['data']['author']['links']['view']));

        // Links
        $this->assertNotEmpty($response['data']['links']);
        $this->assertTrue($this->isValidUrl($response['data']['links']['view']));
        $this->assertTrue($this->isValidUrl($response['data']['links']['edit']));
        $this->assertTrue($this->isValidUrl($response['data']['links']['embed']));

        $this->assertNotEmpty($response['data']['links']['thumbnails']);
        foreach ($response['data']['links']['thumbnails'] as $thumbnail) {
            $this->assertTrue($this->isValidUrl($thumbnail));
        }
    }
}
