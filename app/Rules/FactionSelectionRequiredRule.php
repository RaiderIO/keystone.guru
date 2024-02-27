<?php

namespace App\Rules;

use App\Models\Dungeon;
use App\Models\Faction;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;

class FactionSelectionRequiredRule implements Rule
{
    /**
     * The request control provider instance.
     *
     * @var Request
     */
    public $request;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(ParameterBag $request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     */
    public function passes(string $attribute, $value): bool
    {
        $dungeonId = $this->request->get('dungeon_id');
        $factionId = $this->request->get('faction_id');

        $result = ! empty($value);
        /** @var Collection|Dungeon[] $factionSelectionRequired */
        $factionSelectionRequired = Dungeon::factionSelectionRequired()->get();

        if (in_array(intval($dungeonId), $factionSelectionRequired->pluck('id')->toArray())) {
            $result = in_array(intval($factionId), [Faction::ALL[Faction::FACTION_ALLIANCE], Faction::ALL[Faction::FACTION_HORDE]]);
        }

        return $result;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('rules.faction_selection_required_rule.message');
    }
}
