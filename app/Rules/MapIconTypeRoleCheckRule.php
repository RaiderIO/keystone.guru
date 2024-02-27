<?php

namespace App\Rules;

use App\Models\MapIconType;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class MapIconTypeRoleCheckRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function passes(string $attribute, $value): bool
    {
        if ($value !== null) {
            /** @var MapIconType $mapIconType */
            $mapIconType = MapIconType::where('id', $value)->first();

            // Only allow admins to save admin_only icons
            if ($mapIconType === null || $mapIconType->admin_only && !(Auth::check() && Auth::user()->hasRole('admin'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('rules.map_icon_type_role_check_rule.message');
    }
}
