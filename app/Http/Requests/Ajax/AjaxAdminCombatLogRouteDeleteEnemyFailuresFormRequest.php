<?php

namespace App\Http\Requests\Ajax;

use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxAdminCombatLogRouteDeleteEnemyFailuresFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::findOrFail($this->validated('dungeon_id')));
    }

    /**


     * @return array<string, array<int, string|Rule>|string|Rule>
     */

    public function rules(): array
    {
        return [
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
        ];
    }
}
