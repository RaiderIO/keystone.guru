<?php


namespace App\Service\Discord;

class DiscordApiService implements DiscordApiServiceInterface
{
    private function curl(string $url, array $postBody): string
    {
        // https://stackoverflow.com/questions/51747829/how-to-send-a-embedded-webhook-using-php-discord
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            // Found no way to disable this behaviour from json_encode
            CURLOPT_POSTFIELDS => str_replace('\\\\n', '\\n', json_encode($postBody)),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    function sendMessage(string $webhookUrl, string $message, string $username = null, array $embeds = []): bool
    {
        $result = $this->curl($webhookUrl, ['message' => $message, 'username' => $username]);
        dump($result);
        return true;
    }

    function sendEmbeds(string $webhookUrl, array $embeds): bool
    {
        $result = $this->curl($webhookUrl, ['embeds' => $embeds]);
        dump($result);
        return true;
    }

}