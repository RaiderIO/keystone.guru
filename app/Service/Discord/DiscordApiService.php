<?php


namespace App\Service\Discord;

class DiscordApiService implements DiscordApiServiceInterface
{
    function sendMessage(string $webhookUrl, string $message, string $username = null): bool
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

        return true;
    }

}