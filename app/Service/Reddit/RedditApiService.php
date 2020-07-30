<?php


namespace App\Service\Reddit;

class RedditApiService implements RedditApiServiceInterface
{
    function createPost(string $subject, string $body): bool
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL        => 'https://www.reddit.com/api/v1/access_token',
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode(['grant_type'    => 'refresh_token',
                                               'refresh_token' => env('REDDIT_REFRESH_TOKEN')
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                sprintf('Authorization: %s', base64_encode(sprintf('%s:%s', env('REDDIT_CLIENT_ID'), env('REDDIT_SECRET_KEY')))),
                'Content-Type: x-www-form-urlencoded'
            ]
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($response['access_token'])) {
            $token = $response['access_token'];

            // Use token to send message
        } else {
            return false;
        }


        /**
         * Response:
         * {
         * "access_token": "<token>>",
         * "token_type": "bearer",
         * "expires_in": 3600,
         * "scope": "submit"
         * }
         */
        return true;
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
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}