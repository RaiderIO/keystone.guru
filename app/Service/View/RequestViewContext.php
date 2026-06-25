<?php

namespace App\Service\View;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\User;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use Illuminate\Support\Facades\Auth;

class RequestViewContext implements RequestViewContextInterface
{
    private ?GameServerRegion $userOrDefaultRegion = null;

    private ?Expansion $currentExpansion = null;

    private ?GameVersion $currentUserGameVersion = null;

    private ?bool $isUserAdmin = null;

    private ?bool $isAdFree = null;

    public function __construct(
        private readonly ExpansionServiceInterface   $expansionService,
        private readonly GameVersionServiceInterface $gameVersionService,
    ) {
    }

    public function getUserOrDefaultRegion(): GameServerRegion
    {
        return $this->userOrDefaultRegion ??= GameServerRegion::getUserOrDefaultRegion();
    }

    public function getCurrentExpansion(): Expansion
    {
        return $this->currentExpansion ??= $this->expansionService->getCurrentExpansion($this->getUserOrDefaultRegion());
    }

    public function getCurrentUserGameVersion(): GameVersion
    {
        return $this->currentUserGameVersion ??= $this->gameVersionService->getGameVersion($this->getUser());
    }

    public function isUserAdmin(): bool
    {
        return $this->isUserAdmin ??= (bool)$this->getUser()?->hasRole(Role::ROLE_ADMIN);
    }

    public function isAdFree(): bool
    {
        return $this->isAdFree ??= $this->getUser()?->hasPatreonBenefit(PatreonBenefit::AD_FREE)
            || $this->getUser()?->hasAdFreeGiveaway();
    }

    private function getUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::getUser();

        return $user;
    }
}
