<?php


namespace App\Service\Reddit;

class RedditApiService implements RedditApiServiceInterface
{
    function createPost(string $subreddit, string $subject, string $body): bool
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://www.reddit.com/api/v1/access_token',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'refresh_token',
                'refresh_token' => config('keystoneguru.reddit.api.refresh_token'),
            ]),
            CURLOPT_HTTPHEADER     => [
                sprintf('Authorization: Basic %s',
                    base64_encode(
                        sprintf('%s:%s', config('keystoneguru.reddit.oauth.client_id'), config('keystoneguru.reddit.oauth.secret'))
                    )
                ),
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: keystone.guru/v3.3',
            ],
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        /**
         * Response:
         * {
         * "access_token": "<token>>",
         * "token_type": "bearer",
         * "expires_in": 3600,
         * "scope": "submit"
         * }
         */

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        if (isset($response['access_token'])) {
            $token = $response['access_token'];

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://oauth.reddit.com/api/submit',
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'sr'    => $subreddit,
                    'title' => $subject,
                    'text'  => $body,
                    'kind'  => 'self',
                ]),
                CURLOPT_HTTPHEADER     => [
                    sprintf('Authorization: Bearer %s', $token),
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: keystone.guru/v3.3',
                ],
                CURLOPT_RETURNTRANSFER => 1,
            ]);

            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if ($response['success'] === true) {
                return true;
            }
        }

        return false;
    }


    function sendMessage(string $webhookUrl, string $message, string $username = null): string
    {
        // https://stackoverflow.com/questions/51747829/how-to-send-a-embedded-webhook-using-php-discord
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL        => $webhookUrl,
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode(['content' => $message, 'username' => $username], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
