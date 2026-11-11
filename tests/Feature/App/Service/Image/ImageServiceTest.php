<?php

namespace Tests\Feature\App\Service\Image;

use App\Service\Image\ImageService;
use Imagick;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('ImageService')]
final class ImageServiceTest extends PublicTestCase
{
    #[Test]
    public function convertToItemImage_givenOpaqueImage_writesHexagonWithTransparentCornersAndOpaqueCentre(): void
    {
        // Arrange
        $sourceFile = sprintf('%s/%s.png', sys_get_temp_dir(), uniqid('item_source_', true));
        $targetFile = sprintf('%s/%s.png', sys_get_temp_dir(), uniqid('item_target_', true));

        $source = new Imagick();
        $source->newImage(64, 64, 'red');
        $source->setImageFormat('png');
        $source->writeImage($sourceFile);
        $source->clear();
        $source->destroy();

        try {
            // Act
            $result = new ImageService()->convertToItemImage($sourceFile, $targetFile);

            // Assert
            $this->assertTrue($result);
            $target = new Imagick($targetFile);

            try {
                $this->assertSame(64, $target->getImageWidth());
                $this->assertSame(64, $target->getImageHeight());
                $this->assertEqualsWithDelta(0.0, $target->getImagePixelColor(0, 0)->getColorValue(Imagick::COLOR_ALPHA), 0.01);
                $centre = $target->getImagePixelColor(32, 32);
                $this->assertEqualsWithDelta(1.0, $centre->getColorValue(Imagick::COLOR_ALPHA), 0.01);
                $this->assertEqualsWithDelta(1.0, $centre->getColorValue(Imagick::COLOR_RED), 0.01);
            } finally {
                $target->clear();
                $target->destroy();
            }
        } finally {
            foreach ([$sourceFile, $targetFile] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
