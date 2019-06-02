<?php


namespace App\Service;

interface EchoServerConfigServiceInterface {
    public function getConfig();

    public function getClients();

    public function getClient($appId);

    public function getClientAt($index);
}