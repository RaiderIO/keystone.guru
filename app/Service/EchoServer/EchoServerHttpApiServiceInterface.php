<?php

namespace App\Service\EchoServer;

interface EchoServerHttpApiServiceInterface
{
    public function getHealth(): array;

    public function getStatus(): array;

    public function getChannels(): array;

    public function getChannelInfo($channelName): array;

    public function getChannelUsers($channelName): array;
}
