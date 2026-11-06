<?php

namespace App\Vendor\SemVer;

class Version extends \PHLAK\SemVer\Version
{
    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function getPreRelease(): ?string
    {
        return $this->preRelease;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }
}
