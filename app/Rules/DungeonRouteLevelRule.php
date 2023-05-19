<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class DungeonRouteLevelRule implements Rule
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
     * @param ParameterBag $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $explode = explode(';', $value);

        return count($explode) === 2 && is_numeric($explode[0]) && is_numeric($explode[1]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('rules.faction_selection_required_rule.message');
    }
}
