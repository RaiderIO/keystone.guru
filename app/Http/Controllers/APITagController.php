<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagFormRequest;
use App\Models\DungeonRoute;
use App\Models\Tag;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Teapot\StatusCode\Http;

class APITagController
{
    const CATEGORY_MODEL_CLASS_MAPPING = [
        'dungeonroute' => DungeonRoute::class,
    ];

    /**
     * @param TagFormRequest $request
     *
     * @return Application|ResponseFactory|Response
     */
    public function store(TagFormRequest $request)
    {
        $category = $request->get('category', null);
        if (!in_array($category, self::CATEGORY_MODEL_CLASS_MAPPING)) {
            abort(Http::BAD_REQUEST, __('Invalid request'));
        }

        $tagModel = new Tag();
        $tagModel->model_id = $request->get('id');
        $tagModel->model_class = self::CATEGORY_MODEL_CLASS_MAPPING[$category];
        $tagModel->tag = $request->get('tag');

        if ($tagModel->save()) {
            $result = response()->noContent();
        } else {
            $result = response('Unable to save Tag', Http::INTERNAL_SERVER_ERROR);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Tag $tag
     *
     * @return array|ResponseFactory|Response
     * @throws Exception
     */
    function delete(Request $request, Tag $tag)
    {
        if ($tag->delete()) {
            $result = response()->noContent();
        } else {
            $result = response('Unable to delete Tag', Http::INTERNAL_SERVER_ERROR);
        }

        return $result;
    }
}