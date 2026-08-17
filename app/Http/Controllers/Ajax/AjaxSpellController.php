<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spell\AjaxSpellUpdateFormRequest;
use App\Models\Spell\Spell;

class AjaxSpellController extends Controller
{
    public function update(
        AjaxSpellUpdateFormRequest $request,
        Spell                      $spell,
    ): Spell {
        $validated = $request->validated();

        if (isset($validated['dispel_type'])) {
            $validated['dispel_type'] = sprintf('spelldispeltype.%s', $validated['dispel_type']);
        }

        $spell->update($validated);

        return $spell;
    }
}
