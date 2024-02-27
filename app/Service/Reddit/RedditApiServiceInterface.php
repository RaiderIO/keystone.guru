<?php

namespace App\Service\Reddit;

interface RedditApiServiceInterface
{
    public function createPost(string $subreddit, string $subject, string $body): bool;
}
