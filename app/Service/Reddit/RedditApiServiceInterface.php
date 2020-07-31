<?php


namespace App\Service\Reddit;

interface RedditApiServiceInterface
{
    function createPost(string $subreddit, string $subject, string $body): bool;
}