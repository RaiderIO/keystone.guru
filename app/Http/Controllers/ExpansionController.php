<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\ExpansionFormRequest;
use App\Models\Expansion;

class ExpansionController extends Controller
{
    public function new()
    {
        $headerTitle = __('New expansion');
        return view('admin.expansion.edit', compact('headerTitle'));
    }

    public function edit($id)
    {
        $expansion = new Expansion();
        $expansion = $expansion->find($id);
        if ($expansion === null) {
            abort(500, 'Unable to load expansion');
        }
        $headerTitle = __('Edit expansion');
        return view('admin.expansion.edit', compact('expansion', 'headerTitle'));
    }

    public function update(ExpansionFormRequest $request, $id)
    {
        // Store it and show the edit page again
        return $this->edit($this->_store($request, $id));
    }

    public function savenew(ExpansionFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return redirect()->route("admin.expansion.edit", ["id" => $this->_store($request)]);
    }

    private function _store(ExpansionFormRequest $request, $id = -1)
    {
        $expansion = new Expansion();
        $edit = $id !== -1;
        if ($edit) {
            $expansion = $expansion->find($id);
        }

        $expansion->name = $request->get('name');
        $expansion->icon = $request->get('icon');
        $expansion->color = $request->get('color');

        // Update or insert it
        if (!$expansion->save()) {
            abort(500, 'Unable to save expansion');
        }

        \Session::flash('status', sprintf(__('Expansion %s'), $edit ? __("updated") : __("saved")));

        return $expansion->id;
    }

    public function view()
    {
        $expansions = DB::table('expansions')->select(['id', 'name', 'icon', 'color'])->get();

        return view('admin.expansion.view', compact('expansions'));
    }
}
