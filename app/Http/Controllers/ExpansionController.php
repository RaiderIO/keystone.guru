<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpansionFormRequest;
use App\Models\Expansion;
use Illuminate\Http\Request;

class ExpansionController extends Controller
{
    /**
     * @param ExpansionFormRequest $request
     * @param Expansion $expansion
     * @return mixed
     * @throws \Exception
     */
    public function store($request, Expansion $expansion = null)
    {
        if ($new = ($expansion === null)) {
            $expansion = new Expansion();
        }

        // Something went wrong with saving
        if (!$expansion->saveFromRequest($request, 'expansions')) {
            abort(500, 'Unable to save expansion');
        }

        return $expansion;
    }

    /**
     * Show a page for creating a new expansion.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('admin.expansion.edit', ['headerTitle' => __('New expansion')]);
    }

    /**
     * @param Request $request
     * @param Expansion $expansion
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Expansion $expansion)
    {
        return view('admin.expansion.edit', ['model' => $expansion, 'headerTitle' => __('Edit expansion')]);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param ExpansionFormRequest $request
     * @param Expansion $expansion
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(ExpansionFormRequest $request, Expansion $expansion)
    {
        // Store it and show the edit page again
        $expansion = $this->store($request, $expansion);

        // Message to the user
        \Session::flash('status', __('Expansion updated'));

        // Display the edit page
        return $this->edit($request, $expansion);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param ExpansionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(ExpansionFormRequest $request)
    {
        // Store it and show the edit page
        $expansion = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Expansion created'));

        return redirect()->route('admin.expansion.edit', ["expansion" => $expansion]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.expansion.list', ['models' => Expansion::all()]);
    }
}
