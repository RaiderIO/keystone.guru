<?php

namespace App\Service\MessageBanner;

interface MessageBannerServiceInterface
{
    public function setMessage(?string $message): bool;

    public function getMessage(): ?string;
}
