<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spell\AjaxSpellUpdateFormRequest;
use App\Models\Spell;

class AjaxSpellController extends Controller
{
    public function update(
        AjaxSpellUpdateFormRequest $request,
        Spell                      $spell
    ): Spell {
        $spell->update($request->validated());

        return $spell;
    }
}
