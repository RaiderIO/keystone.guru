<?php

namespace App\Service\Discord;

interface DiscordApiServiceInterface
{
    public function sendMessage(string $webhookUrl, string $message, ?string $username = null, array $embeds = []): bool;

    public function sendEmbeds(string $webhookUrl, array $embeds): bool;
}
