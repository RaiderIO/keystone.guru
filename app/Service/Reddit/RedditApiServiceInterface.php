<?php


namespace App\Service\Reddit;

interface RedditApiServiceInterface
{
    function createPost(string $subject, string $body): bool;
}