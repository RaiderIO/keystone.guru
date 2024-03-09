<?php

namespace App\Rules;

use App\Models\Dungeon;
use App\Models\Faction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;

class FactionSelectionRequiredRule implements ValidationRule
{
    /**
     * The request control provider instance.
     */
    public ParameterBag $request;

    /**
     * Create a new rule instance.
     */
    public function __construct(ParameterBag $request)
    {
        $this->request = $request;
    }

    /**
     * @param string  $attribute
     * @param mixed   $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dungeonId = $this->request->get('dungeon_id');
        $factionId = $this->request->get('faction_id');

        $result = !empty($value);
        /** @var Collection|Dungeon[] $factionSelectionRequired */
        $factionSelectionRequired = Dungeon::factionSelectionRequired()->get();

        if (in_array(intval($dungeonId), $factionSelectionRequired->pluck('id')->toArray())) {
            $result = in_array(intval($factionId), [Faction::ALL[Faction::FACTION_ALLIANCE], Faction::ALL[Faction::FACTION_HORDE]]);
        }

        if (!$result) {
            $fail(__('rules.faction_selection_required_rule.message'));
        }
    }


}
