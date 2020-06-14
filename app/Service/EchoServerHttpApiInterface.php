<?php


namespace App\Service;

interface EchoServerHttpApiInterface
{
    public function getStatus();

    public function getChannels();

    public function getChannelInfo($channelName);

    public function getChannelUsers($channelName);
}