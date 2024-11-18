<?php

namespace App\Models\Interfaces;

interface TracksPageViewInterface
{
    public function trackPageView(int $source): bool;
}
