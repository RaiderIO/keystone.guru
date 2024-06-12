<?php

namespace Tests\Traits;

trait ValidatesUrls
{
    private function isValidUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
