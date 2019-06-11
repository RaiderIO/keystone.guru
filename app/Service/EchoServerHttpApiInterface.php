<?php


namespace App\Service;

interface EchoServerHttpApiInterface
{
    public function getStatus($appId = '');

    public function getChannels($appId = '');

    public function getChannelInfo($channelName, $appId = '');

    public function getChannelUsers($channelName, $appId = '');
}