<?php

namespace App\Http\View\Composers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

readonly class TeamSelectComposer implements ViewComposerInterface
{
    /**
     * Offers the teams the user may add routes to, plus the team that is already selected so an
     * unchanged form does not take the route out of it.
     */
    public function compose(View $view): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            $view->with('teams', collect());

            return;
        }

        $selectedId = (int)($view->getData()['selectedId'] ?? 0);

        $view->with('teams', $user->teams->filter(
            static fn(Team $team): bool => $team->id === $selectedId || $team->canAddRoute($user),
        )->values());
    }
}
