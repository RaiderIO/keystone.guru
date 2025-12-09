<?php

namespace App\Service\Reverb;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Log;
use Teapot\StatusCode;

class ReverbHttpApiService implements ReverbHttpApiServiceInterface
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

    private function signedQuery(string $method, string $path, array $extraParams = [], string $body = ''): array
    {
        $method = strtoupper($method);

        // Important: this must match what the server uses for $request->getUri()->getPath()
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        $secret = config('reverb.apps.apps.0.secret');

        // Subset of params used for signature (exactly what Reverb does)
        $paramsForSignature = $extraParams; // GET, no body_md5

        // These unsets mirror Arr::except() in Controller::verifySignature
        unset(
            $paramsForSignature['auth_signature'],
            $paramsForSignature['body_md5'],
            $paramsForSignature['appId'],
            $paramsForSignature['appKey'],
            $paramsForSignature['channelName'],
        );

        ksort($paramsForSignature);

        $queryForSignature = collect($paramsForSignature)->map(
            function ($value, $key) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                return $key . '=' . $value;
            },
        )->implode('&');

        $stringToSign = implode("\n", [
            $method,
            $path,
            $queryForSignature,
        ]);

        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $secret);

        // For debugging in tinker you can temporarily dd($stringToSign, $params);
        return $params;
    }

    private function doRequestForApp(string $uri): array
    {
        return $this->doRequest(
            sprintf('/apps/%s/%s', config('reverb.apps.apps.0.app_id'), $uri),
        );
    }

    /**
     * @throws Exception
     */
    private function doRequest(string $uri): array
    {
        $result = [];

        if ($this->client !== null) {
            // Perform the API request with the correct auth key
            $query = $this->signedQuery('GET', $uri, [
                // any extra query params for this endpoint go here, e.g. 'filter_by_prefix' => 'presence-'
            ]);

            $response = $this->client->get(
                $uri,
                ['query' => $query],
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
    public function getChannels(): array
    {
        return $this->doRequestForApp('channels')['channels'];
    }

    /**
     * {@inheritDoc}
     **/
    public function getChannelInfo($channelName): array
    {
        return $this->doRequestForApp(sprintf('channels/%s', $channelName));
    }

    /**
     * {@inheritDoc}
     **/
    public function getChannelUsers($channelName): array
    {
        return $this->doRequestForApp(sprintf('channels/%s/users', $channelName))['users'];
    }
}
