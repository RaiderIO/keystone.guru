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
     * @param mixed $value
     */
    public function passes(string $attribute, $value): bool
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
     */
    public function message(): string
    {
        return __('rules.create_route_npc_chronological_rule.message', ['npcs' => implode(', ', $this->failedNpcIndices)]);
    }
}
