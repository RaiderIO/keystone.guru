<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonBenefitRepositoryInterface;

class PatreonBenefitRepository extends DatabaseRepository implements PatreonBenefitRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonBenefit::class);
    }
}
