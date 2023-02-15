<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\Metric\APIMetricFormRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class APIMetricController extends Controller
{
    /**
     * @param APIMetricFormRequest $request
     *
     * @return Application|ResponseFactory|Response
     * @throws AuthorizationException
     */
    public function store(APIMetricFormRequest $request)
    {


        return $result;
    }
}
