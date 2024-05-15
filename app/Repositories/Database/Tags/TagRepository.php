<?php

namespace App\Repositories\Database\Tags;

use App\Models\Tags\Tag;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;

class TagRepository extends DatabaseRepository implements TagRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Tag::class);
    }
}
