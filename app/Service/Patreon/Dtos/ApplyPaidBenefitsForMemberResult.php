<?php

namespace App\Service\Patreon\Dtos;

enum ApplyPaidBenefitsForMemberResult: int
{
    /** The member's benefits are now in sync */
    case Applied = 1;

    /** The member has no Keystone.guru account linked to it - by far the most common outcome, and not a problem */
    case MemberNotLinked = 10;

    /**
     * The campaign hands out a benefit title that is missing from PatreonBenefit::ALL, so the benefits we computed
     * for this member are incomplete and syncing them would revoke benefits the member is still paying for. Needs a
     * code change - the title has to be added to PatreonBenefit::ALL (#3748).
     */
    case UnknownBenefits = 20;

    /**
     * The member is entitled to a tier that the campaign response does not describe, so the benefits
     * computed for them are incomplete and nothing is written.
     */
    case UnknownTiers = 30;
}
