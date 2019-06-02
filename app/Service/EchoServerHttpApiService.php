<?php


namespace App\Service;

use GuzzleHttp\Client;
use Teapot\StatusCode;

class EchoServerHttpApiService implements EchoServerHttpApiInterface
{

    /** @var Client Guzzle client; used for communicating with the echo server API. */
    private $_client;

    /**
     * @var EchoServerConfigService $_configService The service from which we get config values to perform these requests.
     */
    private $_configService;

    /**
     * EchoServerHttpApiService constructor.
     * @param EchoServerConfigService $configService
     * @throws \Exception
     */
    public function __construct(EchoServerConfigService $configService)
    {
        $this->_configService = $configService;
        $port = ($this->_configService->getConfig())->port;

        // Make sure we don't have a trailing slash in the app_url
        $appUrl = trim(env('LARAVEL_ECHO_SERVER_URL'), '/');

        $this->_client = new Client([
            // Base URI is used with relative requests
            'base_uri' => sprintf('%s:%s', $appUrl, $port),
            // You can set any number of default request options.
            'timeout' => 2.0
        ]);
    }

    /**
     * @param $uri
     * @param string $appId
     * @return bool|mixed
     * @throws \Exception
     */
    private function _doRequest($uri, $appId = '')
    {
        $result = false;

        // Find the client based on the app ID
        $client = $appId === '' ? $this->_configService->getClientAt(0) : $this->_configService->getClient($appId);

        // Perform the API request with the correct auth key
        $response = $this->_client->get(sprintf('apps/%s/%s', $client->appId, $uri), ['query' => ['auth_key' => $client->key]]);
        if ($response->getStatusCode() === StatusCode::OK) {
            $result = json_decode((string)$response->getBody());
        }

        return $result;
    }

    /**
     * @param $appId
     * @return mixed
     * @throws \Exception
     */
    public function getStatus($appId = '')
    {
        return $this->_doRequest('status', $appId);
    }

    /**
     * @param $appId
     * @return mixed
     * @throws \Exception
     */
    public function getChannels($appId = '')
    {
        return $this->_doRequest('channels', $appId);
    }

    /**
     * @param $appId
     * @param $channelName
     * @return mixed
     * @throws \Exception
     */
    public function getChannelInfo($channelName, $appId = '')
    {
        return $this->_doRequest(sprintf('channels/%s', $channelName), $appId);
    }

    /**
     * @param $appId
     * @param $channelName
     * @return mixed
     * @throws \Exception
     */
    public function getChannelUsers($channelName, $appId = '')
    {
        return $this->_doRequest(sprintf('channels/%s/users', $channelName), $appId);
    }

}