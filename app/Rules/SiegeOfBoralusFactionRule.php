<?php

namespace App\Rules;

use App\Models\Dungeon;
use App\Models\Faction;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class SiegeOfBoralusFactionRule implements Rule
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
     * @param  ParameterBag $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $dungeonId = $this->request->get('dungeon_id');
        $factionId = $this->request->get('faction_id');

        $result = !empty($value);
        /** @var Dungeon $siegeOfBoralus */
        $siegeOfBoralus = Dungeon::siegeOfBoralus()->first();

        if (intval($dungeonId) === $siegeOfBoralus->id) {
            $validFactions = Faction::whereIn('name', ['Alliance', 'Horde'])->get()->pluck('id')->toArray();
            $result = in_array(intval($factionId), $validFactions);
        }

        return $result;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('You need to select a faction for the Siege of Boralus dungeon.');
    }
}