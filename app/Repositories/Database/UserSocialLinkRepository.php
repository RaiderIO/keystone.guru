<?php

namespace App\Repositories\Database;

use App\Models\UserSocialLink;
use App\Repositories\Interfaces\UserSocialLinkRepositoryInterface;

class UserSocialLinkRepository extends DatabaseRepository implements UserSocialLinkRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(UserSocialLink::class);
    }
}
