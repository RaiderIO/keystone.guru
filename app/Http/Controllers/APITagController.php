<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\Tag\APITagFormRequest;
use App\Http\Requests\Tag\APITagUpdateFormRequest;
use App\Models\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Traits\HasTags;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode;
use Teapot\StatusCode\Http;

class APITagController extends Controller
{
    /**
     * @param Request $request
     * @return Tag[]|Collection
     */
    public function all(Request $request)
    {
        return Tag::all();
    }

    /**
     * @param Request $request
     * @param TagCategory $category
     * @return Tag[]|Collection
     */
    public function list(Request $request, TagCategory $category)
    {
        return Tag::where('tag_category_id', $category->id)->where('user_id', Auth::id())->get();
    }

    /**
     * @param APITagFormRequest $request
     *
     * @return Application|ResponseFactory|Response
     * @throws AuthorizationException
     */
    public function store(APITagFormRequest $request)
    {
        /** @var TagCategory $tagCategory */
        $tagCategory = TagCategory::where('name', $request->get('category'))->firstOrFail();

        $modelId = $request->get('model_id');
        $tagName = $request->get('name');

        // Reconstruct the model that we're trying to tag
        /** @var Builder $query */
        /** @noinspection PhpUndefinedMethodInspection */
        $query = $tagCategory->model_class::query();
        if (in_array($tagCategory->name, [TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM])) {
            /** @var DungeonRoute $dungeonRoute */
            $query = $query->where('public_key', $modelId);
        } else {
            $query = $query->where('id', $modelId);
        }

        /** @var HasTags|Model $model */
        $model = $query->firstOrFail();

        // Now that we know the category and created an instance of the model, check if we may actually do this
        $this->authorize('create-tag', [$tagCategory, $model]);

        //
        if (!$model->hasTag($tagCategory->id, $tagName)) {
            // Get the first tag that has the same name, under the same user, with the same category
            /** @var Tag $similarTag */
            $similarTag = Tag::where('name', $tagName)->where('user_id', Auth::id())->where('tag_category_id', $tagCategory->id)->first();

            // Save the tag we're trying to add
            $tag = new Tag();
            // Technically we can fetch the user_id by going through the model but that's just too much work and slow
            $tag->user_id         = Auth::id();
            $tag->tag_category_id = $tagCategory->id;
            $tag->model_id        = $model->id;
            $tag->model_class     = $tagCategory->model_class;
            $tag->name            = $tagName;
            // Will be null if no similar tag is found which is fine
            $tag->color = optional($similarTag)->color;

            if ($tag->save()) {
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
     * @param APITagUpdateFormRequest $request
     * @param Tag $tag
     * @return Response
     * @throws AuthorizationException
     */
    public function updateAll(APITagUpdateFormRequest $request, Tag $tag)
    {
        $this->authorize('edit', $tag);

        // Update all tags with the same name to the new name and color
        Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('user_id', Auth::id())
            ->update(['name' => $request->get('name'), 'color' => $request->get('color')]);

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param Tag $tag
     * @return Response
     * @throws AuthorizationException
     * @throws Exception
     */
    public function deleteAll(Request $request, Tag $tag)
    {
        $this->authorize('delete', $tag);

        // Update all tags with the same name to the new name and color
        Tag::where('name', $tag->name)
            ->where('tag_category_id', $tag->tag_category_id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param Tag $tag
     *
     * @return array|ResponseFactory|Response
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
