<?php

namespace App\Vendor\SemVer;

use PHLAK\SemVer\Exceptions\InvalidVersionException;

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

    /**
     * Attempt to parse an incomplete version string.
     *
     * Examples: 'v1', 'v1.2', 'v1-beta.4', 'v1.3+007'
     *
     * @param string $version Version string
     *
     * @return self This Version object
     * @throws InvalidVersionException
     *
     */
    public static function parse(string $version): self
    {
        $semverRegex = '/^v?(?<major>\d+)(?:\.(?<minor>\d+)(?:\.(?<patch>\d+))?)?(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

        if (!preg_match($semverRegex, $version, $matches)) {
            throw new InvalidVersionException('Invalid semantic version string provided');
        }

        $version = sprintf('%s.%s.%s', $matches['major'], $matches['minor'] ?? 0, $matches['patch'] ?? 0);

        if (!empty($matches['pre_release'])) {
            $version .= '-' . $matches['pre_release'];
        }

        if (!empty($matches['build'])) {
            $version .= '+' . $matches['build'];
        }

        return new self($version);
    }

}
