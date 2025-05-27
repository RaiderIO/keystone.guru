<?php

namespace App\Service\ReadOnlyMode;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;

class ReadOnlyModeService implements ReadOnlyModeServiceInterface
{
    public function __construct(private readonly CacheServiceInterface $cacheService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setReadOnly(bool $readOnly): bool
    {
        return $this->cacheService->set('read_only_mode', $readOnly);
    }

    /**
     * {@inheritDoc}
     */
    public function isReadOnly(): bool
    {
        return $this->cacheService->has('read_only_mode') && (bool)$this->cacheService->get('read_only_mode') === true;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadOnlyForUser(?User $user): bool
    {
        $result = $this->isReadOnly();

        if ($result && $user !== null) {
            // But if the user is an admin, we allow them to do everything
            $result = !$user->hasRole(Role::ROLE_ADMIN);
        }

        return $result;
    }
}
