<?php

namespace App\Mcp\Tools\Route;

use App\Http\Requests\DungeonRoute\DungeonRouteSubmitFormRequest;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\DungeonDifficulty;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAiSource;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteSaveServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Creates a new, empty route for a dungeon on the dungeon\'s current mapping. The route is always private (unpublished), belongs to no team and is marked as created by an AI; whether it is ever published is up to its owner on keystone.guru, not to these tools. Returns the route, including its public_key for the other tools.')]
class CreateRouteTool extends McpTool
{
    protected string $name = 'create_route';

    public function __construct(
        private readonly DungeonRouteSaveServiceInterface $dungeonRouteSaveService,
        private readonly DungeonRouteFormatter            $dungeonRouteFormatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dungeon'     => $schema->string()->description('The dungeon key or slug, from list_dungeons.')->required(),
            'title'       => $schema->string()->max(DungeonRoute::TITLE_MAX_LENGTH)->description('The route\'s title.')->required(),
            'description' => $schema->string()->max(1000)->description('The route\'s description.'),
            'level_min'   => $schema->integer()->min(1)->description('The lowest keystone level the route is meant for.'),
            'level_max'   => $schema->integer()->min(1)->description('The highest keystone level the route is meant for.'),
            'faction'     => $schema->string()->enum([Faction::FACTION_UNSPECIFIED, Faction::FACTION_ALLIANCE, Faction::FACTION_HORDE])
                ->description('Only needed for dungeons whose enemies differ per faction. Defaults to unspecified.'),
            'affix_group_ids'    => $schema->array()->items($schema->integer())->description('Affix group ids of the current season the route is meant for.'),
            'dungeon_difficulty' => $schema->integer()->enum(DungeonDifficulty::values())->description(sprintf(
                'The speedrun difficulty (raid size), for dungeons that have one: %s. list_dungeons lists the difficulties of each dungeon.',
                implode(', ', array_map(static fn(DungeonDifficulty $difficulty) => sprintf('%d = %s', $difficulty->value, $difficulty->translatedName()), DungeonDifficulty::cases())),
            )),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'dungeon'            => ['required', 'string', 'max:255'],
            'title'              => ['required', 'string', sprintf('max:%d', DungeonRoute::TITLE_MAX_LENGTH)],
            'description'        => ['nullable', 'string', 'max:1000'],
            'level_min'          => ['nullable', 'integer', 'min:1', 'required_with:level_max'],
            'level_max'          => ['nullable', 'integer', 'min:1', 'gte:level_min', 'required_with:level_min'],
            'faction'            => ['nullable', 'string', Rule::in([Faction::FACTION_UNSPECIFIED, Faction::FACTION_ALLIANCE, Faction::FACTION_HORDE])],
            'affix_group_ids'    => ['nullable', 'array'],
            'affix_group_ids.*'  => ['integer'],
            'dungeon_difficulty' => ['nullable', 'integer'],
        ]);

        if (Gate::forUser($user)->denies('create', DungeonRoute::class)) {
            throw new McpToolException(__('mcp.error.request_refused'));
        }

        $dungeon = $this->findActiveDungeon($validated['dungeon']);

        $enabledDifficulties = $dungeon->speedrun_enabled ? $dungeon->getEnabledSpeedrunDifficulties() : [];
        if (isset($validated['dungeon_difficulty']) && !in_array((int)$validated['dungeon_difficulty'], $enabledDifficulties, true)) {
            throw new McpToolException(__('mcp.error.unknown_dungeon_difficulty', ['difficulty' => $validated['dungeon_difficulty']]));
        }

        $this->consumeRateLimit($user, 'create_route', (int)config('keystoneguru.api.mcp.create_route_per_hour'));

        $formData = array_filter([
            'dungeon_id'                => $dungeon->id,
            'dungeon_route_title'       => $validated['title'],
            'dungeon_route_description' => $validated['description'] ?? null,
            'dungeon_route_level'       => isset($validated['level_min']) ? sprintf('%d;%d', $validated['level_min'], $validated['level_max']) : null,
            'faction_id'                => Faction::ALL[$validated['faction'] ?? Faction::FACTION_UNSPECIFIED],
            'route_select_affixes'      => array_map('strval', $validated['affix_group_ids'] ?? []),
            'dungeon_difficulty'        => $validated['dungeon_difficulty'] ?? null,
            'team_id'                   => -1,
        ], static fn($value) => $value !== null);

        $validatedFormData = $this->validateWithFormRequest(DungeonRouteSubmitFormRequest::class, $user, $formData);
        // Never clone a template or flag a demo route from an AI client
        unset($validatedFormData['template'], $validatedFormData['demo']);

        $dungeonRoute = new DungeonRoute();
        if (!$this->dungeonRouteSaveService->save($dungeonRoute, $validatedFormData)) {
            throw new McpToolException(__('mcp.error.unable_to_save_route'));
        }

        $clientName = $this->getOAuthClientName($user);
        DungeonRoute::query()->whereKey($dungeonRoute->id)->update([
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'ai_source'          => DungeonRouteAiSource::fromClientName($clientName)->value,
            'ai_client_name'     => $clientName === null ? null : mb_substr($clientName, 0, 255),
        ]);

        return Response::structured($this->dungeonRouteFormatter->formatRoute($dungeonRoute->fresh()));
    }
}
