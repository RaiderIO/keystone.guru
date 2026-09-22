<?php

namespace App\Http\Controllers\Api\V1\Spec;

/**
 * @OA\Info(
 *     title="Keystone.guru API",
 *     version="1"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="basicAuth",
 *     type="http",
 *     scheme="basic",
 *     description="Authenticate with HTTP Basic auth, using your keystone.guru account's email address as the username and your account password as the password."
 * )
 *
 * @OA\OpenApi(
 *     security={{"basicAuth": {}}}
 * )
 */
class OpenApiSpec
{
}
