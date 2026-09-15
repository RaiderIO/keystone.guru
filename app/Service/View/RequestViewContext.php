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
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class RequestViewContext implements RequestViewContextInterface
{
    /** Laravel's canonical alias for {@see RedirectIfAuthenticated} */
    private const string GUEST_MIDDLEWARE_ALIAS = 'guest';

    private ?GameServerRegion $userOrDefaultRegion = null;

    private ?Expansion $currentExpansion = null;

    private ?GameVersion $currentUserGameVersion = null;

    private ?bool $isUserAdmin = null;

    private ?bool $isAdFree = null;

    private ?bool $isCurrentRouteGuestOnly = null;

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

    public function isCurrentRouteGuestOnly(): bool
    {
        return $this->isCurrentRouteGuestOnly ??= $this->hasGuestMiddleware(Request::route());
    }

    /**
     * Whether the given route's middleware stack contains the `guest` middleware.
     *
     * Middleware is gathered exactly as it was registered, so it can be either Laravel's `guest`
     * alias (what every auth controller here registers) or the class name itself. The router's
     * alias map covers the second spelling, but it cannot be the only check: the map is only
     * complete once the HTTP kernel has synced its aliases into the router, which has not happened
     * in a console/test context, so the alias name is matched literally as well.
     *
     * There is no route at all while rendering an error page outside of routing, hence the guard.
     */
    private function hasGuestMiddleware(mixed $route): bool
    {
        if (!$route instanceof RoutingRoute) {
            return false;
        }

        $middlewareAliases = Route::getMiddleware();

        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            // Strip any parameters, e.g. `guest:web`
            $name = explode(':', $middleware)[0];

            if ($name === self::GUEST_MIDDLEWARE_ALIAS) {
                return true;
            }

            if (($middlewareAliases[$name] ?? null) === RedirectIfAuthenticated::class) {
                return true;
            }

            if ($name === RedirectIfAuthenticated::class) {
                return true;
            }
        }

        return false;
    }

    private function getUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::getUser();

        return $user;
    }
}
