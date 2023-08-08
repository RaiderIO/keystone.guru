<?php


namespace App\Service\Discord;

use App\Service\Traits\Curl;

class DiscordApiService implements DiscordApiServiceInterface
{
    use Curl;

    /**
     * @param string      $webhookUrl
     * @param string      $message
     * @param string|null $username
     * @param array       $embeds
     * @return bool
     */
    public function sendMessage(string $webhookUrl, string $message, string $username = null, array $embeds = []): bool
    {
        $result = $this->curlPost($webhookUrl, ['message' => $message, 'username' => $username]);
        return true;
    }

    /**
     * @param string $webhookUrl
     * @param array  $embeds
     * @return bool
     */
    public function sendEmbeds(string $webhookUrl, array $embeds): bool
    {
        $result = $this->curlPost($webhookUrl, ['embeds' => $embeds]);
        return true;
    }

}
