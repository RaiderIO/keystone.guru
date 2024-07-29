<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Spell;
use Illuminate\Http\Request;

class AjaxSpellController extends Controller
{
    public function toggleVisibility(
        Request $request,
        Spell   $spell
    ): Spell {
        $spell->update([
            'hidden_on_map' => !$spell->hidden_on_map,
        ]);

        return $spell;
    }
}
