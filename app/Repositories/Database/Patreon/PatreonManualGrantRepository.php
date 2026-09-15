<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonManualGrant;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonManualGrantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class PatreonManualGrantRepository extends DatabaseRepository implements PatreonManualGrantRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonManualGrant::class);
    }

    public function hasActiveGrantForUserId(int $userId): bool
    {
        return PatreonManualGrant::query()->active()->where('user_id', $userId)->exists();
    }

    public function getActiveGrantForUserId(int $userId): ?PatreonManualGrant
    {
        return PatreonManualGrant::query()->active()->where('user_id', $userId)->first();
    }

    public function getActiveGrants(): EloquentCollection
    {
        return PatreonManualGrant::query()
            ->active()
            // There are no foreign keys, so a deleted user leaves its grants behind; those have nothing
            // left to show and are dropped here rather than guarded against on every read
            ->whereHas('user')
            ->with(['user.patreonUserLink.patreonBenefits', 'grantedBy'])
            ->orderByDesc('created_at')
            ->get();
    }
}
