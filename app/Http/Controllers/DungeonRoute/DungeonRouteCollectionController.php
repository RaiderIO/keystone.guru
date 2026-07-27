<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Session;

class DungeonRouteCollectionController extends Controller
{
    /**
     * Lists all collections of the currently logged in user.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('collection.index', [
            // The overview shows a route count per collection - counted in the query rather than
            // per row, which would be a query per collection
            'dungeonRouteCollections' => $user->dungeonRouteCollections()
                ->with(['team'])
                ->withCount('dungeonRouteCollectionRoutes')
                ->get(),
        ]);
    }

    /**
     * Shows the form for a brand new collection.
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('collection.new', [
            'dungeonRouteCollection'  => null,
            'ownDungeonRoutes'        => $this->getOwnDungeonRoutes($user),
            'selectedDungeonRouteIds' => [],
            'teams'                   => $user->teams,
        ]);
    }

    /**
     * Stores a brand new collection.
     */
    public function savenew(
        DungeonRouteCollectionFormRequest              $request,
        DungeonRouteCollectionRepositoryInterface      $dungeonRouteCollectionRepository,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if ($user->dungeonRouteCollections()->count() >= DungeonRouteCollection::MAX_COLLECTIONS) {
            Session::flash('warning', __('controller.dungeonroutecollection.flash.max_collections_reached', [
                'max' => DungeonRouteCollection::MAX_COLLECTIONS,
            ]));

            return redirect()->route('collections.index');
        }

        // The collection and its routes are saved together: a failure partway through would
        // otherwise leave an empty collection behind
        $dungeonRouteCollection = DB::transaction(function () use (
            $user,
            $request,
            $dungeonRouteCollectionRepository,
            $dungeonRouteCollectionRouteRepository,
        ): DungeonRouteCollection {
            $dungeonRouteCollection = $dungeonRouteCollectionRepository->create([
                'user_id'            => $user->id,
                'team_id'            => $request->team()?->id,
                'public_key'         => DungeonRouteCollection::generateRandomPublicKey(),
                'published_state_id' => $request->publishedStateId(),
                'name'               => $request->validated('name'),
                'description'        => $request->validated('description'),
            ]);

            $this->syncDungeonRoutes($dungeonRouteCollection, $request->dungeonRoutes(), $dungeonRouteCollectionRouteRepository);

            return $dungeonRouteCollection;
        });

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_created'));

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    /**
     * Shows the form for an existing collection.
     *
     * @throws AuthorizationException
     */
    public function edit(Request $request, DungeonRouteCollection $dungeonRouteCollection): View
    {
        Gate::authorize('edit', $dungeonRouteCollection);

        $dungeonRouteCollection->load(['dungeonRoutes', 'user']);

        return view('collection.edit', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            // Scoped to the collection's own owner, not the acting user - otherwise an admin
            // editing someone else's collection would see an empty picker and no shared teams
            'ownDungeonRoutes'        => $this->getOwnDungeonRoutes($dungeonRouteCollection->user),
            'selectedDungeonRouteIds' => $dungeonRouteCollection->dungeonRoutes->pluck('id')->all(),
            'teams'                   => $dungeonRouteCollection->user->teams,
        ]);
    }

    /**
     * Updates an existing collection.
     *
     * @throws AuthorizationException
     */
    public function update(
        DungeonRouteCollectionFormRequest              $request,
        DungeonRouteCollection                         $dungeonRouteCollection,
        DungeonRouteCollectionRepositoryInterface      $dungeonRouteCollectionRepository,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): RedirectResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        // The collection and its routes are saved together: a failure partway through would
        // otherwise leave the collection renamed while its routes still describe the old state
        DB::transaction(function () use (
            $request,
            $dungeonRouteCollection,
            $dungeonRouteCollectionRepository,
            $dungeonRouteCollectionRouteRepository,
        ): void {
            $dungeonRouteCollectionRepository->update($dungeonRouteCollection, [
                'team_id'            => $request->team()?->id,
                'published_state_id' => $request->publishedStateId(),
                'name'               => $request->validated('name'),
                'description'        => $request->validated('description'),
            ]);

            $this->syncDungeonRoutes($dungeonRouteCollection, $request->dungeonRoutes(), $dungeonRouteCollectionRouteRepository);
        });

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_updated'));

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    /**
     * Deletes an existing collection.
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRouteCollection $dungeonRouteCollection): RedirectResponse
    {
        Gate::authorize('delete', $dungeonRouteCollection);

        try {
            $dungeonRouteCollection->delete();
        } catch (Exception) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_deleted'));

        return redirect()->route('collections.index');
    }

    /**
     * The public page of a collection, shared by its public key.
     *
     * @throws AuthorizationException
     */
    public function view(Request $request, DungeonRouteCollection $dungeonRouteCollection): View
    {
        Gate::authorize('view', $dungeonRouteCollection);

        // The routes render through the shared route card, which needs the same relation set
        // DiscoverService eager loads - lazy loading is disabled, so a miss here is a 500
        $dungeonRouteCollection->load([
            'user',
            'dungeonRoutes.author.iconfile',
            'dungeonRoutes.affixes',
            'dungeonRoutes.ratings',
            'dungeonRoutes.mappingVersion',
            'dungeonRoutes.thumbnails',
            'dungeonRoutes.dungeon',
            'dungeonRoutes.season.expansion',
            // Needed by mayUserView() for team published routes
            'dungeonRoutes.team',
        ]);

        return view('collection.view', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            // A collection being public never publishes the routes in it - an unpublished route
            // stays hidden from everyone but its author
            'dungeonRoutes' => $dungeonRouteCollection->getViewableDungeonRoutes(Auth::user()),
        ]);
    }

    /**
     * The routes a user may put in a collection. Sandbox routes expire, so they are deliberately
     * not offered.
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getOwnDungeonRoutes(User $user): Collection
    {
        return DungeonRoute::query()
            ->where('author_id', $user->id)
            ->whereNull('expires_at')
            ->with(['dungeon'])
            ->orderBy('title')
            ->get();
    }

    /**
     * Replaces the routes of a collection with the passed set, in the passed order. Replacing
     * rather than diffing keeps the ordering trivially correct - a collection is capped at
     * DungeonRouteCollection::MAX_ROUTES rows, so there is nothing to gain from reconciling.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    private function syncDungeonRoutes(
        DungeonRouteCollection                         $dungeonRouteCollection,
        Collection                                     $dungeonRoutes,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): void {
        $dungeonRouteCollection->dungeonRouteCollectionRoutes()->delete();

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            $dungeonRouteCollectionRouteRepository->create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        $dungeonRouteCollection->unsetRelation('dungeonRoutes');
        $dungeonRouteCollection->unsetRelation('dungeonRouteCollectionRoutes');
    }
}
