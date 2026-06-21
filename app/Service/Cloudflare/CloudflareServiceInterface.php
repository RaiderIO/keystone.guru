<?php

namespace App\Service\Cloudflare;

interface CloudflareServiceInterface
{
    /**

     * @return array<int, mixed>
     */

    /**


     * @return array<int, mixed>
     */

    public function getIpRanges(bool $useCache = true): array;

    /**


     * @return array<int, mixed>
     */

    /**



     * @return array<int, mixed>
     */

    public function getIpRangesV4(bool $useCache = true): array;

    /**


     * @return array<int, mixed>
     */

    /**



     * @return array<int, mixed>
     */

    public function getIpRangesV6(bool $useCache = true): array;
}
