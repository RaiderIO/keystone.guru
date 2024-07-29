<?php

namespace App\Repositories\Database;

use App\Models\File;
use App\Repositories\Interfaces\FileRepositoryInterface;

class FileRepository extends DatabaseRepository implements FileRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(File::class);
    }
}
