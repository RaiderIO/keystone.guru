<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpansionFormRequest;
use App\Models\Expansion;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class ExpansionController extends Controller
{
    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function store(ExpansionFormRequest $request, ?Expansion $expansion = null)
    {
        if ($new = ($expansion === null)) {
            $expansion = new Expansion();
        }

        // Something went wrong with saving
        if (! $expansion->saveFromRequest($request, 'expansions')) {
            abort(500, __('controller.expansion.flash.unable_to_save_expansion'));
        }

        return $expansion;
    }

    /**
     * Show a page for creating a new expansion.
     *
     * @return Factory|View
     */
    public function new(): View
    {
        return view('admin.expansion.edit');
    }

    /**
     * @return Factory|View
     */
    public function edit(Request $request, Expansion $expansion): View
    {
        return view('admin.expansion.edit', ['expansion' => $expansion]);
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function update(ExpansionFormRequest $request, Expansion $expansion)
    {
        // Store it and show the edit page again
        $expansion = $this->store($request, $expansion);

        // Message to the user
        Session::flash('status', __('controller.expansion.flash.expansion_updated'));

        // Display the edit page
        return $this->edit($request, $expansion);
    }

    /**
     *
     * @throws Exception
     */
    public function savenew(ExpansionFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $expansion = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.expansion.flash.expansion_created'));

        return redirect()->route('admin.expansion.edit', ['expansion' => $expansion]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list(): View
    {
        return view('admin.expansion.list', ['expansions' => Expansion::all()]);
    }
}
