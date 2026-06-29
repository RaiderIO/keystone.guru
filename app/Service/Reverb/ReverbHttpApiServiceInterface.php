<?php

namespace App\Service\Reverb;

interface ReverbHttpApiServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getHealth(): array;

    /**
     * @return array<string, mixed>
     */
    public function getChannels(): array;

    /**
     * @return array<string, mixed>
     */
    public function getChannelInfo(string $channelName): array;

    /**
     * @return array<string, mixed>
     */
    public function getChannelUsers(string $channelName): array;
}
