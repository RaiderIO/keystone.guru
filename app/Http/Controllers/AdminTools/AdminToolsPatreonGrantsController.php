<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Repositories\Interfaces\Patreon\PatreonManualGrantRepositoryInterface;
use App\Service\Patreon\Dtos\ManualGrantOverviewRow;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;

class AdminToolsPatreonGrantsController extends Controller
{
    public function index(PatreonManualGrantRepositoryInterface $patreonManualGrantRepository): View
    {
        return view('admin.tools.patreon.grants', [
            'manualGrants' => $this->getManualGrantOverview($patreonManualGrantRepository),
        ]);
    }

    public function revoke(
        User                    $user,
        PatreonServiceInterface $patreonService,
    ): RedirectResponse {
        if ($patreonService->revokeManualGrant($user, auth()->user())) {
            Session::flash('status', __('controller.admintools.flash.patreon_manual_grant_revoked', ['user' => $user->name]));
        } else {
            Session::flash('warning', __('controller.admintools.flash.patreon_manual_grant_nothing_to_revoke', ['user' => $user->name]));
        }

        return redirect()->route('admin.tools.patreon.grants.view');
    }

    /**
     * Every user currently holding manually granted benefits, most recently granted first.
     *
     * Grants made before the audit record existed have no row in patreon_manual_grants - the only
     * trace they left is a Patreon link the admin panel fabricated. Those are unioned in here rather
     * than backfilled, because there is nothing truthful to backfill them with: the reason and the
     * granting admin were simply never recorded.
     *
     * @return Collection<int, ManualGrantOverviewRow>
     */
    private function getManualGrantOverview(PatreonManualGrantRepositoryInterface $patreonManualGrantRepository): Collection
    {
        /** @var Collection<int, ManualGrantOverviewRow> $rows */
        $rows           = collect();
        $auditedUserIds = [];

        // whereHas('user') on both queries, rather than a null check per row: there are no foreign keys
        // in this application, so a deleted user does leave orphaned rows behind - they just have
        // nothing to show on this page
        foreach ($patreonManualGrantRepository->getActiveGrants() as $grant) {
            $patreonUserLink  = $grant->user->patreonUserLink;
            $auditedUserIds[] = $grant->user->id;

            $rows->push(new ManualGrantOverviewRow(
                user:               $grant->user,
                grantedAt:          $grant->created_at,
                benefits:           $patreonUserLink === null ? collect() : $patreonUserLink->patreonBenefits,
                isLegacy:           false,
                reason:             $grant->reason,
                grantedByName:      $grant->grantedBy?->name,
                hasRealPatreonLink: $patreonUserLink !== null &&
                                    $patreonUserLink->refresh_token !== PatreonUserLink::PERMANENT_TOKEN,
            ));
        }

        $legacyLinks = PatreonUserLink::query()
            ->with(['user', 'patreonBenefits'])
            ->whereHas('user')
            ->where('refresh_token', PatreonUserLink::PERMANENT_TOKEN)
            ->whereNotIn('user_id', $auditedUserIds)
            ->get();

        foreach ($legacyLinks as $legacyLink) {
            $rows->push(new ManualGrantOverviewRow(
                user:      $legacyLink->user,
                grantedAt: $legacyLink->created_at,
                benefits:  $legacyLink->patreonBenefits,
                isLegacy:  true,
            ));
        }

        return $rows->sortByDesc(static fn(ManualGrantOverviewRow $row) => $row->grantedAt)->values();
    }
}
