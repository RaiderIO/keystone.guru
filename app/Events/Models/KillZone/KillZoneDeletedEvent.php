<?php

namespace App\Events\Models\KillZone;

use App\Events\Models\ModelDeletedEvent;
use App\Models\KillZone\KillZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class KillZoneDeletedEvent extends ModelDeletedEvent
{
    public function __construct(
        Model    $context,
        User     $user,
        KillZone $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'killzone-deleted';
    }
}
