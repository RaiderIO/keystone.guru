<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\APITagFormRequest;
use App\Http\Requests\Tag\APITagUpdateFormRequest;
use App\Models\Interfaces\HasTagsInterface;
use App\Models\Interfaces\TaggableInterface;
use App\Models\Tags\Tag;
use App\Repositories\Interfaces\Tags\TagCategoryRepositoryInterface;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class AjaxTagController extends Controller
{
    /**
     * @return Application|ResponseFactory|Response|Tag
     *
     * @throws AuthorizationException
     */
    public function store(
        APITagFormRequest              $request,
        TagCategoryRepositoryInterface $tagCategoryRepository,
        TagRepositoryInterface         $tagRepository,
    ) {
        /** @var Model&HasTagsInterface $context */
        $context = $request->getContext();

        $tagCategory = $tagCategoryRepository->findByKeyOrFail($request->get('category'));

        $modelId = $request->get('model_id');
        $tagName = $request->get('name');

        // Reconstruct the model that we're trying to tag
        /** @var Model&TaggableInterface $model */
        $model = $tagRepository->findTaggableModelOrFail($tagCategory, $modelId);

        // Now that we know the category and created an instance of the model, check if we may actually do this
        Gate::authorize('create-tag', [
            $tagCategory,
            $model,
            $context,
        ]);

        //
        if (!$model->hasTag($tagCategory->id, $tagName)) {
            // Get the first tag that has the same name, under the same context, with the same category
            /** @var Tag|null $similarTag */
            $similarTag = $tagRepository->findFirstWithNameInContext($tagName, $context->getKey(), $context::class, $tagCategory->id);

            if ($tag = Tag::create([ // @phpstan-ignore if.alwaysTrue
                'context_id'      => $context->getKey(),
                'context_class'   => $context::class,
                'tag_category_id' => $tagCategory->id,
                'model_id'        => $model->getKey(),
                'model_class'     => $tagCategory->model_class,
                'name'            => $tagName,
                'color'           => $similarTag?->color,
            ])) {
                $result = $tag;
            } else {
                $result = response('Unable to save Tag', Http::INTERNAL_SERVER_ERROR);
            }
        } else {
            $result = abort(StatusCode::CONFLICT, 'Tag already exists');
        }

        return $result;
    }

    /**
     * @throws AuthorizationException
     */
    public function updateAll(APITagUpdateFormRequest $request, Tag $tag, TagRepositoryInterface $tagRepository): Response
    {
        Gate::authorize('editAll', $tag);

        // Update all tags with the same name to the new name and color
        $tagRepository->updateAllLikeTag($tag, $request->get('name'), $request->get('color'));

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function deleteAll(Request $request, Tag $tag, TagRepositoryInterface $tagRepository): Response
    {
        Gate::authorize('deleteAll', $tag);

        // Update all tags with the same name to the new name and color
        $tagRepository->deleteAllLikeTag($tag);

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function delete(Request $request, Tag $tag): array|ResponseFactory|Response
    {
        Gate::authorize('delete', $tag);

        if ($tag->delete()) {
            $result = response()->noContent();
        } else {
            $result = response('Unable to delete Tag', Http::INTERNAL_SERVER_ERROR);
        }

        return $result;
    }
}
