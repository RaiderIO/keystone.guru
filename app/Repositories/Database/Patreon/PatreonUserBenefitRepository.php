<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonUserBenefit;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonUserBenefitRepositoryInterface;

class PatreonUserBenefitRepository extends DatabaseRepository implements PatreonUserBenefitRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonUserBenefit::class);
    }
}
