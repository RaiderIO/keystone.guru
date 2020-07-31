<?php

namespace App\Vendor\SemVer;

class Version extends \PHLAK\SemVer\Version
{
    /**
     * @return int
     */
    public function getMajor(): int
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function getMinor(): int
    {
        return $this->minor;
    }

    /**
     * @return int
     */
    public function getPatch(): int
    {
        return $this->patch;
    }

    /**
     * @return string|null
     */
    public function getPreRelease(): ?string
    {
        return $this->preRelease;
    }

    /**
     * @return string|null
     */
    public function getBuild(): ?string
    {
        return $this->build;
    }

}