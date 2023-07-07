<?php

namespace App\Rules;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class CreateRouteNpcChronologicalRule implements Rule
{
    /** @var array|int[] */
    private array $failedNpcIndices = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        foreach ($value as $index => $npc) {
            $engagedAt = $npc['engagedAt'] ?? null;
            $diedAt    = $npc['diedAt'] ?? null;

            if ($engagedAt === null || $diedAt === null) {
                $this->failedNpcIndices[] = $index;
                continue;
            }

            $engagedAtCarbon = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $engagedAt);
            $diedAtCarbon    = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $diedAt);

            if ($diedAtCarbon->isBefore($engagedAtCarbon)) {
                $this->failedNpcIndices[] = $index;
            }
        }

        return empty($this->failedNpcIndices);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return sprintf('Npc(s) %s diedAt must be before engagedAt!', implode(', ', $this->failedNpcIndices));
    }
}
