<?php

namespace App\Service\Reverb;

interface ReverbHttpApiServiceInterface
{
    public function getHealth(): array;

    public function getChannels(): array;

    public function getChannelInfo($channelName): array;

    public function getChannelUsers($channelName): array;
}
