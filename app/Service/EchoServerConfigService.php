<?php

namespace App\Service;


/**
 * This service provides functionality for reading the current laravel echo service and parsing its contents.
 * @package App\Service
 * @author Wouter
 * @since 30/05/2019
 */
class EchoServerConfigService implements EchoServerConfigServiceInterface
{
    /**
     * @return \stdClass
     * @throws \Exception
     */
    public function getConfig()
    {
        $json = file_get_contents(
            base_path(
                sprintf('/etc/supervisor/conf.d/echo/laravel-echo-server-%s.json', env('APP_TYPE'))
            )
        );

        if ($json === false) {
            throw new \Exception('Unable to read echo server configuration, is APP_TYPE set?');
        } else {
            return json_decode($json);
        }
    }

    /**
     * @return \stdClass[]
     * @throws \Exception
     */
    public function getClients()
    {
        return ($this->getConfig())->clients;
    }

    /**
     * @param $appId string
     * @return \stdClass
     * @throws \Exception
     */
    public function getClient($appId)
    {
        $result = false;
        foreach ($this->getConfig()->clients as $client) {
            if ($client->appId === $appId) {
                $result = $client;
                break;
            }
        }

        return $result;
    }

    /**
     * @param $index int
     * @return \stdClass
     * @throws \Exception
     */
    public function getClientAt($index)
    {
        return ($this->getConfig())->clients[$index];
    }

}