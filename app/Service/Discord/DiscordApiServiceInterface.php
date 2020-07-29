<?php


namespace App\Service\Discord;

interface DiscordApiServiceInterface
{
    function sendMessage(string $webhookUrl, string $message, string $username = null): string;
}