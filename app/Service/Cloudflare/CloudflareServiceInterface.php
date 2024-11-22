<?php

namespace App\Service\Cloudflare;

interface CloudflareServiceInterface
{
    public function getIpRanges(bool $useCache = true): array;

    public function getIpRangesV4(bool $useCache = true): array;

    public function getIpRangesV6(bool $useCache = true): array;
}
