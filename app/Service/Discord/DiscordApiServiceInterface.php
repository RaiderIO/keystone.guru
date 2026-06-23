<?php

namespace App\Service\Discord;

interface DiscordApiServiceInterface
{
    /**
     * @param array<int, array<string, mixed>> $embeds
     */
    public function sendMessage(
        string  $webhookUrl,
        string  $message,
        ?string $username = null,
        array   $embeds = [],
    ): bool;

    /**
     * @param array<int, array<string, mixed>> $embeds
     */
    public function sendEmbeds(string $webhookUrl, array $embeds): bool;
}
