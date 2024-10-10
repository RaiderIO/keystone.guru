<?php

namespace App\Service\Image;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

class ImageService implements ImageServiceInterface
{

    /**
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    public function convertToItemImage(string $filePath, string $targetFilePath): bool
    {
        // Initialize the image manager with Imagick driver
        $manager = new ImageManager(new ImagickDriver());

        $imagickImage = new Imagick();

        try {
            // Load the image
            $image = $manager->read($filePath);

            // Get the image dimensions
            $width  = $image->width();
            $height = $image->height();


            $imagickImage->readImage($filePath);
            $imagickImage->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            // Create the hexagon mask and apply it to the image
            $mask = $this->createHexagonMask($width, $height);
            $imagickImage->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);

            // Create the hexagon border mask and apply it to the image
            $mask = $this->createHexagonBorderMask($width, $height, 'black', 3);
            $imagickImage->compositeImage($mask, Imagick::COMPOSITE_ATOP, 0, 0);
//            $mask = $this->createHexagonBorderMask($width, $height, 'white', 2);
//            $imagickImage->compositeImage($mask, Imagick::COMPOSITE_ATOP, 0, 0);
            $mask = $this->createHexagonBorderMask($width, $height, 'white', 3, 3);
            $imagickImage->compositeImage($mask, Imagick::COMPOSITE_ATOP, 0, 0);

            // Save the result
            $imagickImage->writeImage($targetFilePath);
        } finally {
            // Ensure resources are always cleaned up
            $imagickImage->clear();
            $imagickImage->destroy();

            // Clean up the mask as well if it exists
            if (isset($mask)) {
                $mask->clear();
                $mask->destroy();
            }
        }

        return file_exists($targetFilePath);
    }

    private function getHexagonPoints(int $width, int $height, int $margin = 0): array
    {
        $width  = $width - ($margin * 2);
        $height = $height - ($margin * 2);

        $halfWidth          = $width / 2;
        $quarterHeight      = $height / 4;
        $threeQuarterHeight = $height * 0.75;

        $points = [
            ['x' => $halfWidth, 'y' => 0],
            ['x' => $width - 1, 'y' => $quarterHeight], // Sub 1 so that the border is drawn inside the image
            ['x' => $width - 1, 'y' => $threeQuarterHeight],
            ['x' => $halfWidth, 'y' => $height - 1],
            ['x' => 0, 'y' => $threeQuarterHeight],
            ['x' => 0, 'y' => $quarterHeight],
        ];

        // Add the margin to all points to center the hexagon again
        // (we subbed margin*2 from the w/h at the start so this balances it again)
        foreach ($points as &$point) {
            $point['x'] += $margin;
            $point['y'] += $margin;
        }

        return $points;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    private function createHexagonMask(int $width, int $height): Imagick
    {
        // Create a transparent Imagick canvas
        $mask = new Imagick();
        $mask->newImage($width, $height, new ImagickPixel('transparent'));

        // Set up the drawing object
        $draw = new ImagickDraw();

        $draw->polygon($this->getHexagonPoints($width, $height)); // Draw the hexagon shape again for filling
        $mask->drawImage($draw);                                  // Fill the hexagon shape

        return $mask;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    private function createHexagonBorderMask(
        int    $width,
        int    $height,
        string $color,
        int    $strokeWidth,
        int    $margin = 0
    ): Imagick {
        // Set up the drawing object for the border
        $mask = new Imagick();
        $mask->newImage($width, $height, new ImagickPixel('transparent'));

        // Define the stroke color and width for the border
        $borderDraw = new ImagickDraw();
        $borderDraw->setStrokeColor(new ImagickPixel($color)); // Border color
        $borderDraw->setStrokeWidth($strokeWidth);
        $borderDraw->setStrokeAntialias(true);

        // Define hexagon vertices
        $hexagonPoints = $this->getHexagonPoints($width, $height, $margin);

        // Draw the hexagon outline (the border)
        $borderDraw->line($hexagonPoints[0]['x'], $hexagonPoints[0]['y'], $hexagonPoints[1]['x'], $hexagonPoints[1]['y']);
        $borderDraw->line($hexagonPoints[1]['x'], $hexagonPoints[1]['y'], $hexagonPoints[2]['x'], $hexagonPoints[2]['y']);
        $borderDraw->line($hexagonPoints[2]['x'], $hexagonPoints[2]['y'], $hexagonPoints[3]['x'], $hexagonPoints[3]['y']);
        $borderDraw->line($hexagonPoints[3]['x'], $hexagonPoints[3]['y'], $hexagonPoints[4]['x'], $hexagonPoints[4]['y']);
        $borderDraw->line($hexagonPoints[4]['x'], $hexagonPoints[4]['y'], $hexagonPoints[5]['x'], $hexagonPoints[5]['y']);
        $borderDraw->line($hexagonPoints[5]['x'], $hexagonPoints[5]['y'], $hexagonPoints[0]['x'], $hexagonPoints[0]['y']);

        $mask->drawImage($borderDraw); // Apply the border drawing to the mask

        return $mask;
    }
}
