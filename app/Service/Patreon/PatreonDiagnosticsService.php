<?php

namespace App\Service\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use App\Repositories\Interfaces\Patreon\PatreonSyncRunRepositoryInterface;
use App\Service\Patreon\Dtos\ApplyPaidBenefitsForMemberResult;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonCampaignTierDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonMemberDiagnostics;
use App\Service\Patreon\Dtos\Diagnostics\PatreonSyncDryRun;
use App\Service\Patreon\Dtos\Diagnostics\PatreonUserDiagnostics;
use App\Service\Patreon\Dtos\PatreonMemberSyncPlan;
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

        $member              = null;
        $emailDriftCandidate = null;

        $campaignBenefits = $this->patreonService->loadCampaignBenefits();
        $campaignTiers    = $this->patreonService->loadCampaignTiers();
        $campaignMembers  = $this->patreonService->loadCampaignMembers();

        // A truncated member list is not searched: not finding the member in it would wrongly read as
        // "the campaign no longer lists this patron"
        if ($campaignBenefits !== null && $campaignTiers !== null && $campaignMembers !== null && !$campaignMembers->truncated) {
            $linkEmail = $patreonUserLink?->email;

            foreach ($campaignMembers->members as $campaignMember) {
                $memberEmail = $campaignMember['attributes']['email'] ?? null;
                if (!is_string($memberEmail) || $memberEmail === '') {
                    continue;
                }

                if ($linkEmail !== null && mb_strtolower($memberEmail) === mb_strtolower($linkEmail)) {
                    $member = PatreonMemberDiagnostics::fromPlan(
                        $this->patreonService->planPaidBenefitsForMember($campaignBenefits, $campaignTiers, $campaignMember),
                        $campaignMember,
                        self::maskEmail($memberEmail),
                    );

                    continue;
                }

                // The link stores the patron's Patreon email as it was at link time; the campaign reports
                // what it is now. A member carrying the *account's* email while the link carries another
                // is a patron who changed their Patreon email and has been unmatched ever since
                if (mb_strtolower($memberEmail) === mb_strtolower($user->email)) {
                    $emailDriftCandidate = self::maskEmail($memberEmail);
                }
            }
        }

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
