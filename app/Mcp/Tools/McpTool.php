<?php

namespace App\Mcp\Tools;

use App\Features\Mcp;
use App\Mcp\Exceptions\McpToolException;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Every keystone.guru MCP tool runs as the user the OAuth access token belongs to: the Mcp feature must be active
 * for them, the token must carry the tool's scope, and a tool that touches a route only ever finds the caller's own.
 *
 * Tool output carries structured game data and the caller's own content only - never another user's free text - so
 * content hosted on keystone.guru cannot steer the caller's AI.
 */
abstract class McpTool extends Tool
{
    private const int RATE_LIMIT_DECAY_SECONDS = 3600;

    /**
     * The scope the access token must carry. A token with routes:write may also use the routes:read tools.
     */
    abstract protected function requiredScope(): ApiTokenScope;

    /**
     * @throws McpToolException
     * @throws ValidationException
     * @throws HttpException       from the shared save logic (route limits, unconvertible locations)
     */
    abstract protected function run(Request $request, User $user): Response|ResponseFactory;

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            return $this->run($request, $this->authorizeCaller($request));
        } catch (McpToolException $exception) {
            return Response::error($exception->getMessage());
        } catch (HttpException $exception) {
            // abort(422, ...) from the shared save logic (route limits, unconvertible locations) carries a message
            // meant for the user; anything without one stays opaque
            return Response::error($exception->getMessage() !== '' ? $exception->getMessage() : __('mcp.error.request_refused'));
        }
    }

    /**
     * @throws McpToolException
     */
    private function authorizeCaller(Request $request): User
    {
        $user = $request->user();
        if (!$user instanceof User) {
            throw new McpToolException(__('mcp.error.unauthenticated'));
        }

        if (!Feature::for($user)->active(Mcp::class)) {
            throw new McpToolException(__('mcp.error.feature_inactive'));
        }

        $requiredScope = $this->requiredScope();
        $token         = $user->token();
        $hasScope      = $token !== null && (
            $token->can($requiredScope->value) ||
            ($requiredScope === ApiTokenScope::RoutesRead && $token->can(ApiTokenScope::RoutesWrite->value))
        );

        if (!$hasScope) {
            throw new McpToolException(__('mcp.error.missing_scope', ['scope' => $requiredScope->value]));
        }

        return $user;
    }

    /**
     * Finds one of the caller's own routes. Someone else's route answers exactly like a route that does not exist. A
     * route on a team is left out too: its teammates can edit its title, description and pulls, and that is free text
     * of other users the tools must never hand to the caller's AI.
     *
     * @throws McpToolException
     */
    protected function findOwnDungeonRoute(User $user, string $publicKey, string $ability): DungeonRoute
    {
        $dungeonRoute = app(DungeonRouteRepositoryInterface::class)->findOwnedOutsideTeamByPublicKey($user, $publicKey);

        if ($dungeonRoute === null || Gate::forUser($user)->denies($ability, $dungeonRoute)) {
            throw new McpToolException(__('mcp.error.route_not_found', ['route' => $publicKey]));
        }

        return $dungeonRoute;
    }

    /**
     * The name the OAuth client behind the caller's access token registered itself with, e.g. "Claude Code".
     */
    protected function getOAuthClientName(User $user): ?string
    {
        $accessToken = $user->token();
        $clientId    = $accessToken instanceof AccessToken ? $accessToken->oauth_client_id : null;
        if ($clientId === null) {
            return null;
        }

        return Passport::client()->newQuery()->whereKey($clientId)->value('name');
    }

    /**
     * Finds an active dungeon of an active expansion - the dungeons a route may be created for - by key or slug.
     *
     * @throws McpToolException
     */
    protected function findActiveDungeon(string $dungeonKeyOrSlug): Dungeon
    {
        $dungeon = app(DungeonRepositoryInterface::class)->findActiveInActiveExpansionByKeyOrSlug($dungeonKeyOrSlug);

        if ($dungeon === null) {
            throw new McpToolException(__('mcp.error.dungeon_not_found', ['dungeon' => $dungeonKeyOrSlug]));
        }

        return $dungeon;
    }

    /**
     * Counts one call against a per-user hourly limit of this tool family, refusing the call once it is spent.
     *
     * @throws McpToolException
     */
    protected function consumeRateLimit(User $user, string $limitName, int $maxPerHour): void
    {
        $key = sprintf('mcp:%s:%d', $limitName, $user->id);

        if (RateLimiter::tooManyAttempts($key, $maxPerHour)) {
            throw new McpToolException(__('mcp.error.rate_limited', ['seconds' => RateLimiter::availableIn($key)]));
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);
    }

    /**
     * Runs the given data through a Form Request of the web/ajax endpoints - the same rules, preparation and
     * authorize() - so a tool accepts exactly what the editor would.
     *
     * @param  class-string<FormRequest> $formRequestClass
     * @param  array<string, mixed>      $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validateWithFormRequest(string $formRequestClass, User $user, array $data): array
    {
        /** @var FormRequest $formRequest */
        $formRequest = $formRequestClass::create('/', 'POST', $data);
        $formRequest->setContainer(app())
            ->setRedirector(app(Redirector::class))
            ->setUserResolver(static fn() => $user);

        $formRequest->validateResolved();

        return $formRequest->validated();
    }
}
