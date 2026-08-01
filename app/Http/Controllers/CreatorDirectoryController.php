<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatorDirectoryFormRequest;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\View\View;

class CreatorDirectoryController extends Controller
{
    /**
     * The creator directory: everyone with enough published routes who has not opted out, with an
     * optional name search.
     */
    public function index(
        CreatorDirectoryFormRequest      $request,
        CreatorDirectoryServiceInterface $creatorDirectoryService,
    ): View {
        $search = $request->search();

        return view('creator.directory', [
            'creators' => $creatorDirectoryService->paginateCreators($search),
            'search'   => $search,
        ]);
    }
}
