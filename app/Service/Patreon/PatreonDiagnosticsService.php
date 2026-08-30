<?php

namespace App\Service\Patreon;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Repositories\Interfaces\Patreon\PatreonSyncRunRepositoryInterface;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitHolderDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliation;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignTierDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonMemberDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonSyncDryRun;
use App\Service\Patreon\Dtos\Diagnostics\PatreonUserDiagnostics;
use App\Service\Patreon\Dtos\PatreonMemberSyncPlan;
use App\Service\Patreon\Dtos\PatreonOverEntitlementReason;
use Illuminate\Support\Str;

/**
 * Read-only as to Patreon state and benefit rows. Not side-effect free: reaching the Patreon API goes
 * through `PatreonService::loadAdminUser()`, which persists a refreshed admin token when the stored one
 * has expired.
 */
class PatreonDiagnosticsService implements PatreonDiagnosticsServiceInterface
{
    /** How many members of a single kind are listed in a dry run. */
    private const int MAX_LISTED_MEMBERS = 25;

    /**
     * How many over-entitled accounts a reconciliation lists. Higher than the dry run's cap above,
     * because this list is the actionable output rather than a sample of a campaign-wide symptom - but
     * still a cap, since the counts alongside it are the true totals.
     */
    private const int MAX_LISTED_HOLDERS = 100;

    public function __construct(
        private readonly PatreonServiceInterface           $patreonService,
        private readonly PatreonSyncRunRepositoryInterface $patreonSyncRunRepository,
    ) {
    }

    public function getCampaignDiagnostics(): ?PatreonCampaignDiagnostics
    {
        $campaignBenefits = $this->patreonService->loadCampaignBenefits();
        $campaignTiers    = $this->patreonService->loadCampaignTiers();

        if ($campaignBenefits === null || $campaignTiers === null) {
            return null;
        }

        $benefitTitlesById = [];
        foreach ($campaignBenefits as $campaignBenefit) {
            if (isset($campaignBenefit['id'], $campaignBenefit['attributes']['title'])) {
                $benefitTitlesById[(string)$campaignBenefit['id']] = (string)$campaignBenefit['attributes']['title'];
            }
        }

        $tiers = [];
        foreach ($campaignTiers as $campaignTier) {
            /** @var array<int, array<string, mixed>> $tierBenefitData */
            $tierBenefitData = $campaignTier['relationships']['benefits']['data'] ?? [];

            $benefitTitles = [];
            foreach ($tierBenefitData as $benefitData) {
                $title = $benefitTitlesById[(string)$benefitData['id']] ?? null;
                if ($title !== null) {
                    $benefitTitles[] = $title;
                }
            }

            $tiers[] = new PatreonCampaignTierDiagnostics(
                tierId: (string)$campaignTier['id'],
                title: (string)($campaignTier['attributes']['title'] ?? ''),
                benefitTitles: $benefitTitles,
                unknownBenefitTitles: array_values(array_filter(
                    $benefitTitles,
                    static fn(string $title) => !isset(PatreonBenefit::ALL[$title]),
                )),
            );
        }

        $allTitles = array_values(array_unique($benefitTitlesById));

        return new PatreonCampaignDiagnostics(
            tiers: $tiers,
            knownBenefitTitles: array_values(array_filter($allTitles, static fn(string $title) => isset(PatreonBenefit::ALL[$title]))),
            unknownBenefitTitles: array_values(array_filter($allTitles, static fn(string $title) => !isset(PatreonBenefit::ALL[$title]))),
        );
    }

    public function getSyncDryRun(): ?PatreonSyncDryRun
    {
        $campaignBenefits = $this->patreonService->loadCampaignBenefits();
        $campaignTiers    = $this->patreonService->loadCampaignTiers();

        if ($campaignBenefits === null || $campaignTiers === null) {
            return null;
        }

        // Every member a truncated fetch missed would report as one about to lose their benefits
        $campaignMembers = $this->patreonService->loadCampaignMembers();
        if ($campaignMembers === null || $campaignMembers->truncated) {
            return null;
        }

        /** @var array<string, int> $resultCounts */
        $resultCounts = [];
        $losing       = [];
        $gaining      = [];
        $blocked      = [];

        foreach ($campaignMembers->members as $member) {
            $plan = $this->patreonService->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

            $resultCounts[$plan->result->name] = ($resultCounts[$plan->result->name] ?? 0) + 1;

            if ($plan->result === ApplyPaidBenefitsForMemberResult::UnknownTiers ||
                $plan->result === ApplyPaidBenefitsForMemberResult::UnknownBenefits) {
                $this->collect($blocked, $plan, $member);

                continue;
            }

            if ($plan->benefitsToRevoke !== []) {
                $this->collect($losing, $plan, $member);
            }

            if ($plan->benefitsToAdd !== []) {
                $this->collect($gaining, $plan, $member);
            }
        }

        return new PatreonSyncDryRun(
            pageCount: $campaignMembers->pageCount,
            memberCount: count($campaignMembers->members),
            resultCounts: $resultCounts,
            membersLosingBenefits: $losing,
            membersGainingBenefits: $gaining,
            membersBlocked: $blocked,
        );
    }

    public function getUserDiagnostics(User $user): PatreonUserDiagnostics
    {
        $user->load(['patreonUserLink']);
        $patreonUserLink = $user->patreonUserLink;

        // Rows the account's own pointer does not name: the sync's email match uses ->first(), so a second
        // row for the same account makes which one gets written to arbitrary
        /** @var array<int, int> $duplicateLinkIds */
        $duplicateLinkIds = PatreonUserLink::query()
            ->where('user_id', $user->id)
            ->when($patreonUserLink !== null, static fn($builder) => $builder->whereKeyNot($patreonUserLink->id))
            ->pluck('id')
            ->all();

        $member    = null;
        $linkEmail = $patreonUserLink?->email;
        /** @var array<int, string> $lowercasedMemberEmails */
        $lowercasedMemberEmails = [];

        $campaignBenefits = $this->patreonService->loadCampaignBenefits();
        $campaignTiers    = $this->patreonService->loadCampaignTiers();
        $campaignMembers  = $this->patreonService->loadCampaignMembers();

        // A truncated member list is not searched: not finding the member in it would wrongly read as
        // "the campaign no longer lists this patron"
        if ($campaignBenefits !== null && $campaignTiers !== null && $campaignMembers !== null && !$campaignMembers->truncated) {
            foreach ($campaignMembers->members as $campaignMember) {
                $memberEmail = $campaignMember['attributes']['email'] ?? null;
                if (!is_string($memberEmail) || $memberEmail === '') {
                    continue;
                }

                $lowercasedMemberEmails[] = mb_strtolower($memberEmail);

                if ($linkEmail !== null && mb_strtolower($memberEmail) === mb_strtolower($linkEmail)) {
                    $member = PatreonMemberDiagnostics::fromPlan(
                        $this->patreonService->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $campaignMember),
                        $campaignMember,
                        self::maskEmail($memberEmail),
                    );

                    continue;
                }
            }
        }

        // The link stores whatever the patron's Patreon email was at link time, and the campaign reports
        // what it is now - see findEmailDriftCandidate() for why the two differing matters. Shared with
        // the reconciliation report so both name the same accounts by the same rule (#4386)
        $emailDriftCandidate = $this->findEmailDriftCandidate($lowercasedMemberEmails, $user->email, $linkEmail);

        return new PatreonUserDiagnostics(
            userId: $user->id,
            username: $user->name,
            patreonUserLinkId: $patreonUserLink?->id,
            maskedLinkEmail: self::maskEmail($patreonUserLink?->email),
            maskedAccountEmail: self::maskEmail($user->email),
            manuallyGranted: $patreonUserLink !== null && $patreonUserLink->manually_granted,
            lastSeenAt: $patreonUserLink?->last_seen_at,
            lastSyncResult: $patreonUserLink?->last_sync_result?->name,
            storedBenefits: $user->getPatreonBenefits()->values()->all(),
            duplicateLinkIds: $duplicateLinkIds,
            member: $member,
            emailDriftCandidate: $emailDriftCandidate,
            latestSyncRun: $this->patreonSyncRunRepository->getMostRecent(1)->first(),
        );
    }

    /**
     * Cross-references the benefit rows in the database against the campaign, to find every account
     * holding more than it currently pays for (#4386).
     *
     * The hourly sync revokes benefits only from members the campaign returns *and* can resolve, which
     * makes it structurally blind to the accounts that matter most here: a patron whose Patreon email
     * changed, or who is gone from the campaign entirely, is never matched and so is never revoked from.
     * `getSyncDryRun()` above walks members and cannot see them either. This walks the other direction.
     */
    public function getBenefitReconciliation(): ?PatreonBenefitReconciliation
    {
        $campaignBenefits = $this->patreonService->loadCampaignBenefits();
        $campaignTiers    = $this->patreonService->loadCampaignTiers();

        if ($campaignBenefits === null || $campaignTiers === null) {
            return null;
        }

        // A partial member list makes every member it does not contain look like an account the campaign
        // no longer matches - which is the exact thing this report is looking for, so a truncated fetch
        // would fabricate the entire finding rather than merely understate it (#4373)
        $campaignMembers = $this->patreonService->loadCampaignMembers();
        if ($campaignMembers === null || $campaignMembers->truncated) {
            return null;
        }

        /** @var array<int, bool> $matchedLinkIds Keyed by link id, so the lookup below stays O(1) over a campaign of thousands */
        $matchedLinkIds = [];
        /** @var array<int, PatreonOverEntitlementReason> $blockedReasonsByLinkId */
        $blockedReasonsByLinkId = [];
        /** @var array<int, bool> $stuckLinkIds Matched links holding an excess the sync will never revoke */
        $stuckLinkIds = [];
        /** @var array<int, string> $lowercasedMemberEmails */
        $lowercasedMemberEmails = [];
        $downgradedCount        = 0;

        foreach ($campaignMembers->members as $member) {
            $memberEmail = $member['attributes']['email'] ?? null;
            if (is_string($memberEmail) && $memberEmail !== '') {
                $lowercasedMemberEmails[] = mb_strtolower($memberEmail);
            }

            $plan = $this->patreonService->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $member);

            $patreonUserLink = $plan->patreonUserLink;
            if ($patreonUserLink === null) {
                continue;
            }

            // Recorded even for the excluded accounts below, because this set is what marks a link as
            // "the campaign still knows about you" further down - excluding an account from the report
            // must not turn it into an unmatched one
            $matchedLinkIds[$patreonUserLink->id] = true;

            if ($plan->manuallyGranted || $this->isExcludedFromReconciliation($patreonUserLink)) {
                continue;
            }

            if ($plan->result === ApplyPaidBenefitsForMemberResult::UnknownTiers) {
                $blockedReasonsByLinkId[$patreonUserLink->id] = PatreonOverEntitlementReason::SyncBlockedUnknownTiers;

                continue;
            }

            if ($plan->result === ApplyPaidBenefitsForMemberResult::UnknownBenefits) {
                $blockedReasonsByLinkId[$patreonUserLink->id] = PatreonOverEntitlementReason::SyncBlockedUnknownBenefits;

                continue;
            }

            // What the matched link itself holds beyond its tiers - which is not the same thing as the
            // plan's revoke list. That list is diffed against the *account's* patreonUserLink pointer,
            // and with duplicate link rows the pointer and the email-matched link are different rows
            $excessOnMatchedLink = array_diff(
                $patreonUserLink->patreonbenefits->pluck('key')->all(),
                $plan->resolvedBenefits,
            );

            if ($excessOnMatchedLink === []) {
                continue;
            }

            // Anything the sync's own revoke list does not name is excess it will never delete, no matter
            // how often it runs - so it belongs in a bucket with a name rather than in the count of
            // downgrades that correct themselves within the hour
            if (array_diff($excessOnMatchedLink, $plan->benefitsToRevoke) !== []) {
                $stuckLinkIds[$patreonUserLink->id] = true;

                continue;
            }

            $downgradedCount++;
        }

        // Only links that actually hold something can be over-entitled. Manually granted links are
        // filtered in SQL; admins cannot be, since their entitlement comes from a role rather than a row
        $holders = PatreonUserLink::query()
            ->has('patreonUserBenefits')
            ->where('refresh_token', '!=', PatreonUserLink::PERMANENT_TOKEN)
            ->with(['user.roles', 'patreonbenefits'])
            ->get()
            ->reject(fn(PatreonUserLink $patreonUserLink) => $this->isExcludedFromReconciliation($patreonUserLink));

        $unmatchedHolders = [];
        $blockedHolders   = [];
        $unmatchedCount   = 0;
        $blockedCount     = 0;

        foreach ($holders as $patreonUserLink) {
            // Checked before the matched test on purpose: a blocked member *is* matched, and reporting it
            // as unmatched would name the wrong cause and send someone looking for a deleted patron
            if (isset($blockedReasonsByLinkId[$patreonUserLink->id])) {
                $blockedCount++;
                $this->collectHolder($blockedHolders, $patreonUserLink, $blockedReasonsByLinkId[$patreonUserLink->id], null);

                continue;
            }

            if (isset($stuckLinkIds[$patreonUserLink->id])) {
                $unmatchedCount++;
                $this->collectHolder($unmatchedHolders, $patreonUserLink, PatreonOverEntitlementReason::DuplicateLinkAmbiguity, null);

                continue;
            }

            // Matched, resolvable, and any excess it holds is an ordinary downgrade already counted above
            if (isset($matchedLinkIds[$patreonUserLink->id])) {
                continue;
            }

            $emailDriftCandidate = $this->findEmailDriftCandidate(
                $lowercasedMemberEmails,
                $patreonUserLink->user?->email,
                $patreonUserLink->email,
            );

            $unmatchedCount++;
            $this->collectHolder(
                $unmatchedHolders,
                $patreonUserLink,
                $emailDriftCandidate === null ? PatreonOverEntitlementReason::NoCampaignMember : PatreonOverEntitlementReason::EmailDrift,
                $emailDriftCandidate,
            );
        }

        return new PatreonBenefitReconciliation(
            holderCount: $holders->count(),
            unmatchedCount: $unmatchedCount,
            blockedCount: $blockedCount,
            downgradedCount: $downgradedCount,
            unmatchedHolders: $unmatchedHolders,
            blockedHolders: $blockedHolders,
        );
    }

    /**
     * Admins hold every benefit key through `User::getPatreonBenefits()` without a single benefit row
     * behind it, so without this every admin reads as maximally over-entitled and buries the real
     * findings. A link whose user is gone is deliberately *not* excluded - an orphaned row still holding
     * benefit grants is a genuine finding, and `?? false` keeps it in rather than dropping it silently.
     */
    private function isExcludedFromReconciliation(PatreonUserLink $patreonUserLink): bool
    {
        return $patreonUserLink->user?->hasRole(Role::ROLE_ADMIN) ?? false;
    }

    /**
     * The masked campaign email that matches the *account's* address while the link carries a different
     * one - the fingerprint of a patron who changed their Patreon email after linking, which the sync's
     * email match then reads as "never linked" forever after (#4373).
     *
     * @param array<int, string> $lowercasedMemberEmails
     */
    private function findEmailDriftCandidate(array $lowercasedMemberEmails, ?string $accountEmail, ?string $linkEmail): ?string
    {
        if ($accountEmail === null || $accountEmail === '') {
            return null;
        }

        $lowercasedAccountEmail = mb_strtolower($accountEmail);

        // The link already carries the account's address, so no member can match one without the other
        if ($linkEmail !== null && mb_strtolower($linkEmail) === $lowercasedAccountEmail) {
            return null;
        }

        return in_array($lowercasedAccountEmail, $lowercasedMemberEmails, true) ? self::maskEmail($accountEmail) : null;
    }

    /**
     * @param array<int, PatreonBenefitHolderDiagnostics> $target
     */
    private function collectHolder(
        array                        &                        $target,
        PatreonUserLink              $patreonUserLink,
        PatreonOverEntitlementReason $reason,
        ?string                      $emailDriftCandidate,
    ): void {
        if (count($target) >= self::MAX_LISTED_HOLDERS) {
            return;
        }

        $user = $patreonUserLink->user;

        /** @var array<int, int> $duplicateLinkIds */
        $duplicateLinkIds = $user === null ? [] : PatreonUserLink::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($patreonUserLink->id)
            ->pluck('id')
            ->all();

        $target[] = new PatreonBenefitHolderDiagnostics(
            reason: $reason,
            userId: $user?->id,
            username: $user?->name,
            patreonUserLinkId: $patreonUserLink->id,
            maskedLinkEmail: self::maskEmail($patreonUserLink->email),
            maskedAccountEmail: self::maskEmail($user?->email),
            // Read from the link's own rows rather than User::getPatreonBenefits(), which answers with
            // every key for an admin and with nothing at all for an orphaned link
            storedBenefits: $patreonUserLink->patreonbenefits->pluck('key')->values()->all(),
            lastSeenAt: $patreonUserLink->last_seen_at,
            lastSyncResult: $patreonUserLink->last_sync_result?->name,
            duplicateLinkIds: $duplicateLinkIds,
            emailDriftCandidate: $emailDriftCandidate,
        );
    }

    /**
     * @param array<int, PatreonMemberDiagnostics> $target
     * @param array<string, mixed>                 $member
     */
    private function collect(array &$target, PatreonMemberSyncPlan $plan, array $member): void
    {
        if (count($target) >= self::MAX_LISTED_MEMBERS) {
            return;
        }

        $target[] = PatreonMemberDiagnostics::fromPlan($plan, $member, self::maskEmail($plan->memberEmail));
    }

    /** Keeps the first character and the domain, so two addresses can still be told apart at a glance. */
    private static function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        $atPosition = mb_strrpos($email, '@');

        return $atPosition === false || $atPosition < 2
            ? Str::mask($email, '*', 1)
            : Str::mask($email, '*', 1, $atPosition - 1);
    }
}
