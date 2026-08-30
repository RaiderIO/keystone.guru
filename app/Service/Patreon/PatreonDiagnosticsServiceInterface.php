<?php

namespace App\Service\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliation;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonSyncDryRun;
use App\Service\Patreon\Dtos\Diagnostics\PatreonUserDiagnostics;

/**
 * Read-only views onto what the hourly Patreon sync is doing.
 */
interface PatreonDiagnosticsServiceInterface
{
    /** Null when the campaign could not be loaded at all - the tiers or benefits request failed. */
    public function getCampaignDiagnostics(): ?PatreonCampaignDiagnostics;

    /** Null when the campaign or its member list could not be loaded - including a truncated fetch. */
    public function getSyncDryRun(): ?PatreonSyncDryRun;

    /**
     * The account's Patreon state from both sides. The database half always resolves; the campaign half
     * is filled in only when the Patreon API is reachable, so this never fails outright.
     */
    public function getUserDiagnostics(User $user): PatreonUserDiagnostics;

    /**
     * Every account holding more benefits than the campaign grants it, cross-referenced from the
     * database side so it also covers the accounts the campaign no longer matches at all (#4386).
     *
     * Null when the campaign or its member list could not be loaded - including a truncated fetch, which
     * would otherwise make every unfetched member's account look like one the campaign has dropped.
     */
    public function getBenefitReconciliation(): ?PatreonBenefitReconciliation;
}
