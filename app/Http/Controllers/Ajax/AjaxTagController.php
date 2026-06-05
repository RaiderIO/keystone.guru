<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\APITagFormRequest;
use App\Http\Requests\Tag\APITagUpdateFormRequest;
use App\Models\Interfaces\HasTagsInterface;
use App\Models\Interfaces\TaggableInterface;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class AjaxTagController extends Controller
{
    /**
     * @return Collection<Tag>
     */
    public function all(Request $request)
    {
        return Tag::all();
    }

    /**
     * @return Application|ResponseFactory|Response|Tag
     *
     * @throws AuthorizationException
     */
    public function store(
        APITagFormRequest $request,
    ) {
        $validated = $request->validated();

        $contextPublicKey = $validated['context'];
        $contextClass     = $validated['context_class'];

        /** @var Model&HasTagsInterface $context */
        $context = match ($contextClass) {
            'user'  => User::where('public_key', $contextPublicKey)->firstOrFail(),
            'team'  => Team::where('public_key', $contextPublicKey)->firstOrFail(),
            default => abort(StatusCode::BAD_REQUEST, 'Invalid context class'),
        };

        /** @var TagCategory $tagCategory */
        $tagCategory = TagCategory::where('name', $request->get('category'))->firstOrFail();

        $modelId = $request->get('model_id');
        $tagName = $request->get('name');

        // Reconstruct the model that we're trying to tag
        /** @var Builder $query */
        $query = $tagCategory->model_class::query();
        if (in_array($tagCategory->name, [
            TagCategory::DUNGEON_ROUTE_PERSONAL,
            TagCategory::DUNGEON_ROUTE_TEAM,
        ])) {
            $query = $query->where('public_key', $modelId);
        } else {
            $query = $query->where('id', $modelId);
        }

        /** @var Model&TaggableInterface $model */
        $model = $query->firstOrFail();

        // Now that we know the category and created an instance of the model, check if we may actually do this
        Gate::authorize('create-tag', [
            $tagCategory,
            $model,
        ]);

        //
        if (!$model->hasTag($tagCategory->id, $tagName)) {
            // Get the first tag that has the same name, under the same context, with the same category
            /** @var Tag|null $similarTag */
            $similarTag = Tag::where('name', $tagName)
                ->where('context_id', $context->getKey())
                ->where('context_class', $context::class)
                ->where('tag_category_id', $tagCategory->id)
                ->first();

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
    public function updateAll(APITagUpdateFormRequest $request, Tag $tag): Response
    {
        Gate::authorize('edit', $tag);

        // Update all tags with the same name to the new name and color
        Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('context_id', $tag->context_id)
            ->where('context_class', $tag->context_class)
            ->update([
                'name'  => $request->get('name'),
                'color' => $request->get('color'),
            ]);

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function deleteAll(Request $request, Tag $tag): Response
    {
        Gate::authorize('delete', $tag);

        // Update all tags with the same name to the new name and color
        Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('context_id', $tag->context_id)
            ->where('context_class', $tag->context_class)
            ->delete();

        return response()->noContent();
    }

    /**
     * @return array|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function delete(Request $request, Tag $tag)
    {
        if ($tag->delete()) {
            $result = response()->noContent();
        } else {
            $result = response('Unable to delete Tag', Http::INTERNAL_SERVER_ERROR);
        }

        return $result;
    }
}
