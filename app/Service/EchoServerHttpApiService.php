<?php


namespace App\Service;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Log;
use Teapot\StatusCode;

class EchoServerHttpApiService implements EchoServerHttpApiServiceInterface
{

    /** @var Client Guzzle client; used for communicating with the echo server API. */
    private Client $_client;

    public function __construct()
    {
        // Make sure we don't have a trailing slash in the app_url
        $appUrl = trim(config('keystoneguru.echo.url'), '/');

        try {
            $this->_client = new Client([
                // Base URI is used with relative requests
                'base_uri' => sprintf('%s:%s', $appUrl, config('keystoneguru.echo.port')),
                // You can set any number of default request options.
                'timeout'  => 2.0,
            ]);
        } catch (InvalidArgumentException $ex) {
            report($ex);

            Log::error('Unable to connect to echo server service!');

            throw $ex;
        }
    }

    /**
     * @param $uri
     * @return array
     * @throws Exception
     */
    private function _doRequest($uri): array
    {
        $result = [];

        if ($this->_client !== null) {
            // Perform the API request with the correct auth key
            $response = $this->_client->get(
                sprintf('apps/%s/%s', config('keystoneguru.echo.client.app_id'), $uri),
                ['query' => ['auth_key' => config('keystoneguru.echo.client.key')]]
            );
            if ($response->getStatusCode() === StatusCode::OK) {
                $result = json_decode((string)$response->getBody(), true);
            } else {
                throw new Exception(sprintf('Unable to perform request to %s, retrieved status code %s', $uri, $response->getStatusCode()));
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     **/
    public function getStatus(): array
    {
        return $this->_doRequest('status');
    }

    /**
     * @inheritDoc
     **/
    public function getChannels(): array
    {
        return $this->_doRequest('channels')['channels'];
    }

    /**
     * @inheritDoc
     **/
    public function getChannelInfo($channelName): array
    {
        return $this->_doRequest(sprintf('channels/%s', $channelName));
    }

    /**
     * @inheritDoc
     **/
    public function getChannelUsers($channelName): array
    {
        return $this->_doRequest(sprintf('channels/%s/users', $channelName))['users'];
    }

}
