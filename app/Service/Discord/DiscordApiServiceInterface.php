<?php


namespace App\Service\Discord;

interface DiscordApiServiceInterface
{
    function sendMessage(string $webhookUrl, string $message, string $username = null, array $embeds = []): bool;
    function sendEmbeds(string $webhookUrl, array $embeds): bool;
}