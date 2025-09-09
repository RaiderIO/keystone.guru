<?php

namespace App\Service\Discord;

use App\Service\Traits\Curl;

class DiscordApiService implements DiscordApiServiceInterface
{
    use Curl;

    public function sendMessage(string $webhookUrl, string $message, ?string $username = null, array $embeds = []): bool
    {
        $result = $this->curlPost($webhookUrl, [
            'message'  => $message,
            'username' => $username,
        ]);

        return true;
    }

    public function sendEmbeds(string $webhookUrl, array $embeds): bool
    {
        $result = $this->curlPost($webhookUrl, ['embeds' => $embeds]);

        return true;
    }
}
