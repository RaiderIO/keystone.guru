<?php

namespace Tests\Traits;

trait ValidatesUrls
{
    protected function isValidUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
