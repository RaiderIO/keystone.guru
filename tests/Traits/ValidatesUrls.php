<?php

namespace Tests\Traits;

trait ValidatesUrls
{
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
