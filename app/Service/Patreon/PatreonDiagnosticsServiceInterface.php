<?php

namespace App\Service\Patreon;

use App\Models\User;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonSyncDryRun;
use App\Service\Patreon\Dtos\Diagnostics\PatreonUserDiagnostics;

/**
 * Read-only views onto what the hourly Patreon sync is doing, for diagnosing a member who did not get
 * their benefits (#4373).
 *
 * "Read-only" is meant about Patreon state and benefit rows: nothing here grants, revokes or links
 * anything. It is not entirely side-effect free, because reaching the Patreon API at all goes through
 * `PatreonService::loadAdminUser()`, which persists a refreshed admin token when the stored one has
 * expired. That write is the same one the hourly sync performs and is unavoidable without a second
 * token path.
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
