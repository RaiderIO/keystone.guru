<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDungeonRouteFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Session;

class AdminDungeonRouteController extends Controller
{
    private const MAX_RESULTS = 500;

    public function index(Request $request): View
    {
        $rawDungeonId     = $request->integer('dungeon_id', -1);
        $dungeonId        = $rawDungeonId > 0 ? $rawDungeonId : null;
        $publishedStateId = $request->integer('published_state_id', 0) ?: null;
        $author           = $request->string('author')->toString();
        $publicKey        = $request->string('public_key')->toString();

        $query = DungeonRoute::with(['author', 'dungeon', 'publishedState'])
            ->when($dungeonId, fn($q) => $q->where('dungeon_id', $dungeonId))
            ->when($publishedStateId, fn($q) => $q->where('published_state_id', $publishedStateId))
            ->when($author !== '', fn($q) => $q->whereHas(
                'author',
                fn($q) => $q->where('name', 'like', sprintf('%%%s%%', $author)),
            ))
            ->when($publicKey !== '', fn($q) => $q->where('public_key', $publicKey))
            ->orderByDesc('created_at');

        $total   = $query->count();
        $limited = $total > self::MAX_RESULTS;

        return view('admin.dungeonroute.list', [
            'models'          => $query->limit(self::MAX_RESULTS)->get(),
            'publishedStates' => PublishedState::all()->pluck('name', 'id'),
            'filters'         => [
                'dungeon_id'         => $dungeonId,
                'published_state_id' => $publishedStateId,
                'author'             => $author,
                'public_key'         => $publicKey,
            ],
            'limited' => $limited,
        ]);
    }

    public function edit(DungeonRoute $dungeonRoute): View
    {
        return view('admin.dungeonroute.edit', [
            'dungeonRoute'    => $dungeonRoute->load(['author', 'dungeon', 'publishedState']),
            'publishedStates' => PublishedState::all()->pluck('name', 'id'),
        ]);
    }

    public function update(AdminDungeonRouteFormRequest $request, DungeonRoute $dungeonRoute): RedirectResponse
    {
        $dungeonRoute->update($request->validated());

        Session::flash('status', __('controller.admin.dungeonroute.flash.updated'));

        return redirect()->route('admin.dungeonroute.edit', ['dungeonRoute' => $dungeonRoute->id]);
    }

    public function destroy(DungeonRoute $dungeonRoute): RedirectResponse
    {
        Gate::authorize('delete', $dungeonRoute);

        $dungeonRoute->delete();

        Session::flash('status', __('controller.admin.dungeonroute.flash.deleted'));

        return redirect()->route('admin.dungeonroutes');
    }

    public function claim(DungeonRoute $dungeonRoute): RedirectResponse
    {
        $dungeonRoute->author_id = Auth::id();
        $dungeonRoute->save();

        Session::flash('status', __('controller.admin.dungeonroute.flash.claimed'));

        return redirect()->route('admin.dungeonroute.edit', ['dungeonRoute' => $dungeonRoute->id]);
    }
}
