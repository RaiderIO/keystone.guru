<?php

namespace App\Rules;

use App\Models\MapIconType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class MapIconTypeRoleCheckRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null) {
            /** @var MapIconType $mapIconType */
            $mapIconType = MapIconType::where('id', $value)->first();

            // Only allow admins to save admin_only icons
            if ($mapIconType === null || $mapIconType->admin_only && !(Auth::check() && Auth::user()->hasRole('admin'))) {
                $fail(__('rules.map_icon_type_role_check_rule.message'));
            }
        }
    }
}
