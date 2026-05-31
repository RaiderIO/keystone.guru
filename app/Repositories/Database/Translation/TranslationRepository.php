<?php

namespace App\Repositories\Database\Translation;

use App\Models\Translation\Translation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Translation\TranslationRepositoryInterface;

class TranslationRepository extends DatabaseRepository implements TranslationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Translation::class);
    }
}
