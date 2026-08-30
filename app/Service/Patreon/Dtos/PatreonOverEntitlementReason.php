<?php

namespace App\Service\Patreon\Dtos;

/**
 * Why an account holds Patreon benefits the campaign does not currently justify (#4386).
 *
 * The cases are ordered by how much attention they need: the first two are accounts no sync will ever
 * correct on its own, the next two are accounts a sync deliberately refuses to touch, and the last one
 * corrects itself within the hour.
 */
enum PatreonOverEntitlementReason: string
{
    /**
     * The campaign lists no member matching this link's email at all. Either the patron cancelled and
     * Patreon stopped listing them, or they deleted their Patreon account. Their benefits are frozen at
     * whatever the last successful sync left behind, and nothing will ever revoke them.
     */
    case NoCampaignMember = 'no_campaign_member';

    /**
     * A campaign member carries the *account's* email while the link carries a different one - the
     * fingerprint of a patron who changed their Patreon email after linking. The sync matches links by
     * email, so it has silently read as MemberNotLinked ever since, and it looks exactly like a patron
     * who never linked at all.
     */
    case EmailDrift = 'email_drift';

    /**
     * The member is entitled to a tier the campaign response does not describe, so the sync bails out
     * rather than compute an incomplete benefit set (#4373). Correct, but it leaves whatever the account
     * already held in place until the tier is resolved.
     */
    case SyncBlockedUnknownTiers = 'sync_blocked_unknown_tiers';

    /**
     * The member is entitled to a benefit title missing from PatreonBenefit::ALL, so the sync bails out
     * rather than revoke the benefit that title was renamed from (#3748). Same shape as the case above:
     * needs a code change before the account can move.
     */
    case SyncBlockedUnknownBenefits = 'sync_blocked_unknown_benefits';

    /**
     * The member is matched and resolvable, but the excess sits on a link the sync will not clear.
     *
     * The benefit diff is computed from `User::getPatreonBenefits()`, which reads the account's own
     * `patreonUserLink` pointer, while the sync writes to the link it matched the member's email to.
     * With duplicate link rows those are not the same row, so the revoke list describes one link's
     * benefits and is applied to another's - and whatever the matched link holds beyond its tiers is
     * never in that list. Nothing revokes it, however many times the sync runs.
     */
    case DuplicateLinkAmbiguity = 'duplicate_link_ambiguity';

    /**
     * The member is matched and resolvable, and simply holds more than their current tiers grant - the
     * ordinary downgrade. The next hourly sync revokes the difference by itself, so this needs no action
     * and is only ever reported as a count.
     */
    case Downgraded = 'downgraded';
}
