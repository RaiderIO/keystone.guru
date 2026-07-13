<?php

namespace App\Service\Discord;

use App\Service\Traits\Curl;

class DiscordApiService implements DiscordApiServiceInterface
{
    use Curl;

    /**
     * @param array<int, array<string, mixed>> $embeds
     */
    public function sendMessage(string $webhookUrl, string $message, ?string $username = null, array $embeds = []): bool
    {
        $result = $this->curlPost($webhookUrl, [
            'message'  => $message,
            'username' => $username,
        ]);

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $embeds
     */
    public function sendEmbeds(string $webhookUrl, array $embeds): bool
    {
        $result = $this->curlPost($webhookUrl, ['embeds' => $embeds]);

        return true;
    }
}
