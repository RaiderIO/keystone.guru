<?php

namespace App\Rules;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class CreateRouteNpcChronologicalRule implements ValidationRule
{
    /** @var array|int[] */
    private array $failedNpcIndices = [];

    /**
     * @param string  $attribute
     * @param mixed   $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
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

        if (!empty($this->failedNpcIndices)) {
            $fail(__('rules.create_route_npc_chronological_rule.message', ['npcs' => implode(', ', $this->failedNpcIndices)]));
        }
    }
}
