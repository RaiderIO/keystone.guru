<?php

namespace App\Service\EchoServer;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Log;
use Teapot\StatusCode;

class EchoServerHttpApiService implements EchoServerHttpApiServiceInterface
{
    /** @var Client Guzzle client; used for communicating with the echo server API. */
    private Client $client;

    public function __construct()
    {
        // Make sure we don't have a trailing slash in the app_url
        $appUrl = trim((string)config('reverb.apps.apps.0.options.host'), '/');

        try {
            $this->client = new Client([
                // Base URI is used with relative requests
                'base_uri' => sprintf('%s:%s', $appUrl, config('reverb.apps.apps.0.options.port')),
                // You can set any number of default request options.
                'timeout' => 2.0,
            ]);
        } catch (InvalidArgumentException $invalidArgumentException) {
            report($invalidArgumentException);

            Log::error('Unable to connect to echo server service!');

            throw $invalidArgumentException;
        }
    }

    /**
     * @throws Exception
     */
    private function doRequest($uri): array
    {
        $result = [];

        if ($this->client !== null) {
            // Perform the API request with the correct auth key
            $response = $this->client->get(
                sprintf('apps/%s/%s', config('reverb.apps.apps.0.app_id'), $uri),
                ['query' => ['auth_key' => config('reverb.apps.apps.0.key')]],
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
     * {@inheritDoc}
     **/
    public function getHealth(): array
    {
        return $this->doRequest('up');
    }

    /**
     * {@inheritDoc}
     **/
    public function getStatus(): array
    {
        return $this->doRequest('status');
    }

    /**
     * {@inheritDoc}
     **/
    public function getChannels(): array
    {
        return $this->doRequest('channels')['channels'];
    }

    /**
     * {@inheritDoc}
     **/
    public function getChannelInfo($channelName): array
    {
        return $this->doRequest(sprintf('channels/%s', $channelName));
    }

    /**
     * {@inheritDoc}
     **/
    public function getChannelUsers($channelName): array
    {
        return $this->doRequest(sprintf('channels/%s/users', $channelName))['users'];
    }
}
