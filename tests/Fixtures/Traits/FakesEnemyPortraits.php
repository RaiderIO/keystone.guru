<?php

namespace Tests\Fixtures\Traits;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A fake portrait directory, holding `unknown.png` like the real one, with helpers to put 64x64 portrait tiles in it.
 *
 * @mixin TestCase
 */
trait FakesEnemyPortraits
{
    protected function fakeEnemyPortraits(bool $withUnknownPortrait = true): Filesystem
    {
        $disk = Storage::fake((string)config('keystoneguru.raider_io.open_world_npcs.portraits_disk'));
        if ($withUnknownPortrait) {
            $disk->put('unknown.png', $this->createPortraitPng());
        }

        return $disk;
    }

    /**
     * A tile with a drawn portrait: a light square on the pipeline's black frame.
     */
    protected function createPortraitPng(): string
    {
        $image = imagecreatetruecolor(64, 64);
        imagefilledrectangle($image, 16, 16, 47, 47, (int)imagecolorallocate($image, 200, 150, 100));

        return $this->toPng($image);
    }

    /**
     * A tile the client drew no portrait into: the pipeline's black frame with a little compression noise.
     */
    protected function createBlankPng(): string
    {
        $image = imagecreatetruecolor(64, 64);
        imagesetpixel($image, 10, 10, (int)imagecolorallocate($image, 8, 8, 8));

        return $this->toPng($image);
    }

    private function toPng(\GdImage $image): string
    {
        ob_start();
        imagepng($image);

        return (string)ob_get_clean();
    }
}
