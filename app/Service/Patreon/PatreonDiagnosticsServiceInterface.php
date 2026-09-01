<?php

namespace App\Service\Patreon;

use App\Models\User;
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
}
