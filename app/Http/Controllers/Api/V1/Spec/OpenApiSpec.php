<?php

namespace App\Http\Controllers\Api\V1\Spec;

/**
 * @OA\Info(
 *     title="Keystone.guru API",
 *     version="1"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     description="Authenticate with an OAuth 2 access token: a personal access token created on the API tokens tab of your profile, or a token issued through the authorization code flow (/oauth/authorize, /oauth/token). Tokens carry scopes: routes:read, routes:write, combatlog:read, combatlog:write and admin."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="basicAuth",
 *     type="http",
 *     scheme="basic",
 *     description="DEPRECATED - use bearerAuth. Authenticate with HTTP Basic auth, using your keystone.guru account's email address as the username and your account password as the password. Responses carry Deprecation and Sunset headers announcing the date this scheme stops working."
 * )
 *
 * @OA\OpenApi(
 *     security={{"bearerAuth": {}}, {"basicAuth": {}}}
 * )
 */
class OpenApiSpec
{
}
