<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\MappingVersion\APIMappingVersionFormRequest;
use App\Models\Mapping\MappingVersion;

class AjaxMappingVersionController extends Controller
{
    public function store(APIMappingVersionFormRequest $request, MappingVersion $mappingVersion): MappingVersion
    {
        $updateResult = $mappingVersion->update($request->validated());

        if (!$updateResult) {
            abort(500);
        }

        return $mappingVersion;
    }
}
