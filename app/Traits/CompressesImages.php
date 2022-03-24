<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 12-12-2018
 * Time: 14:56
 */

namespace App\Traits;

trait CompressesImages
{
    /**
     * Optimizes PNG file with pngquant 1.8 or later (reduces file size of 24-bit/32-bit PNG images).
     *
     * You need to install pngquant 1.8 on the server (ancient version 1.0 won't work).
     * There's package for Debian/Ubuntu and RPM for other distributions on http://pngquant.org
     *
     * @param $pathToPngFile string - path to any PNG file, e.g. $_FILE['file']['tmp_name']
     * @param $maxQuality int - conversion quality, useful values from 60 to 100 (smaller number = smaller file)
     * @return string - content of PNG file after conversion
     * @throws \Exception
     */
    private function _compressPng(string $pathToPngFile, int $maxQuality = 90)
    {
        if (!file_exists($pathToPngFile)) {
            throw new \Exception("File does not exist: $pathToPngFile");
        }

        // guarantee that quality won't be worse than that.
        $minQuality = 60;

        // '-' makes it use stdout, required to save to $compressed_png_content variable
        // '<' makes it read from the given file path
        // escapeshellarg() makes this safe to use with any path
        $compressedPngContent = shell_exec("pngquant --quality=$minQuality-$maxQuality - < " . escapeshellarg($pathToPngFile));

        if (!$compressedPngContent) {
            throw new \Exception("Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?");
        }

        return $compressedPngContent;
    }

    /**
     * Compresses a PNG from a source to a target file.
     *
     * @param $source string
     * @param $target string
     * @throws \Exception
     */
    public function compressPng(string $source, string $target)
    {
        // this will ensure that $pathToPngFile points to compressed file
        // and avoid re-compressing if it's been done already
        if (!file_exists($target)) {
            file_put_contents($target, $this->_compressPng($source));
        }

        // and now, for example, you can output the compressed file:
        header("Content-Type: image/png");
        header('Content-Length: ' . filesize($target));
        readfile($target);
    }
}
