<?php


namespace App\Service;

use GuzzleHttp\Client;
use Teapot\StatusCode;

class EchoServerHttpApiService implements EchoServerHttpApiInterface
{

    /** @var Client Guzzle client; used for communicating with the echo server API. */
    private $_client;

    /**
     * EchoServerHttpApiService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        // Make sure we don't have a trailing slash in the app_url
        $appUrl = trim(config('keystoneguru.echo.url'), '/');

        try {
            $this->_client = new Client([
                // Base URI is used with relative requests
                'base_uri' => sprintf('%s:%s', $appUrl, config('keystoneguru.echo.port')),
                // You can set any number of default request options.
                'timeout' => 2.0
            ]);
        } catch( \InvalidArgumentException $ex ) {
            \Log::error('Unable to connect to echo server service!');
        }
    }

    /**
     * @param $uri
     * @return bool|mixed
     * @throws \Exception
     */
    private function _doRequest($uri)
    {
        $result = false;

        if( $this->_client !== null ) {
            // Perform the API request with the correct auth key
            $response = $this->_client->get(
                sprintf('apps/%s/%s', config('keystoneguru.echo.client.app_id'), $uri),
                ['query' => ['auth_key' => config('keystoneguru.echo.client.key')]]
            );
            if ($response->getStatusCode() === StatusCode::OK) {
                $result = json_decode((string)$response->getBody());
            }
        }

        return $result;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getStatus()
    {
        return $this->_doRequest('status');
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getChannels()
    {
        return $this->_doRequest('channels');
    }

    /**
     * @param $channelName
     * @return mixed
     * @throws \Exception
     */
    public function getChannelInfo($channelName)
    {
        return $this->_doRequest(sprintf('channels/%s', $channelName));
    }

    /**
     * @param $channelName
     * @return mixed
     * @throws \Exception
     */
    public function getChannelUsers($channelName)
    {
        return $this->_doRequest(sprintf('channels/%s/users', $channelName));
    }

}