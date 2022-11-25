<?php


namespace App\Service\EchoServer;

interface EchoServerHttpApiServiceInterface
{
    /**
     * @return array
     */
    public function getStatus(): array;

    /**
     * @return array
     */
    public function getChannels(): array;

    /**
     * @param $channelName
     * @return array
     */
    public function getChannelInfo($channelName): array;

    /**
     * @param $channelName
     * @return array
     */
    public function getChannelUsers($channelName): array;
}
