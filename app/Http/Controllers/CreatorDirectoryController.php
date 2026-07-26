<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatorDirectoryFormRequest;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\View\View;

class CreatorDirectoryController extends Controller
{
    /**
     * The creator directory: everyone with enough published routes who has not opted out, with an
     * optional name search and an optional filter on the kind of collections they share.
     */
    public function index(
        CreatorDirectoryFormRequest      $request,
        CreatorDirectoryServiceInterface $creatorDirectoryService,
    ): View {
        $search   = $request->search();
        $category = $request->dungeonRouteCollectionCategory();

        return view('creator.directory', [
            'creators'         => $creatorDirectoryService->paginateCreators($search, $category?->id),
            'search'           => $search,
            'categories'       => DungeonRouteCollectionCategory::all(),
            'selectedCategory' => $category,
        ]);
    }
}
