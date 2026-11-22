<?php

namespace App\Mcp\Tools\Route;

use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSubmitFormRequest;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\CharacterClassSpecialization;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteSaveServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TEXT'
    Changes the settings of one of your routes, like the route editor's settings tab: title, description, key level
    range, affix groups, faction, seasonal index, route attributes, dungeon start, pull gradient and the party
    composition (up to five members, each a class, specialization and race; ids from get_route_options). Only the
    fields you pass change. The route stays private and on its team.
    TEXT)]
class UpdateRouteSettingsTool extends McpTool
{
    use ChangesDungeonRoute;

    private const int PARTY_SIZE = 5;

    protected string $name = 'update_route_settings';

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
            'route'                      => $schema->string()->description('The route\'s public_key.')->required(),
            'title'                      => $schema->string()->max(DungeonRoute::TITLE_MAX_LENGTH),
            'description'                => $schema->string()->max(1000),
            'level_min'                  => $schema->integer()->min(1),
            'level_max'                  => $schema->integer()->min(1),
            'affix_group_ids'            => $schema->array()->items($schema->integer())->description('Affix group ids of the current season, replacing the current ones.'),
            'faction'                    => $schema->string()->enum([Faction::FACTION_UNSPECIFIED, Faction::FACTION_ALLIANCE, Faction::FACTION_HORDE]),
            'seasonal_index'             => $schema->integer()->min(0)->description('Which seasonal rotation of the dungeon the route is for, where it has one.'),
            'attribute_ids'              => $schema->array()->items($schema->integer())->description('Route attribute ids, replacing the current ones.'),
            'dungeon_start_id'           => $schema->integer()->description('Where the route starts, from get_route_options with the dungeon.'),
            'pull_gradient'              => $schema->string()->max(2000)->description('The gradient pulls are colored along, e.g. "0 #ff0000,100 #00ff00"; an empty string removes it.'),
            'pull_gradient_apply_always' => $schema->boolean()->description('Recolor every pull along the gradient whenever pulls change.'),
            'party'                      => $schema->array()->max(self::PARTY_SIZE)->items($schema->object(static fn(JsonSchema $schema) => [
                'class_id'          => $schema->integer(),
                'specialization_id' => $schema->integer(),
                'race_id'           => $schema->integer(),
            ]))->description('The party composition, replacing the current one.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route'                      => ['required', 'string', 'max:255'],
            'title'                      => ['nullable', 'string', sprintf('max:%d', DungeonRoute::TITLE_MAX_LENGTH)],
            'description'                => ['nullable', 'string', 'max:1000'],
            'level_min'                  => ['nullable', 'integer', 'min:1', 'required_with:level_max'],
            'level_max'                  => ['nullable', 'integer', 'min:1', 'gte:level_min', 'required_with:level_min'],
            'affix_group_ids'            => ['nullable', 'array'],
            'affix_group_ids.*'          => ['integer'],
            'faction'                    => ['nullable', 'string', Rule::in([Faction::FACTION_UNSPECIFIED, Faction::FACTION_ALLIANCE, Faction::FACTION_HORDE])],
            'seasonal_index'             => ['nullable', 'integer', 'min:0'],
            'attribute_ids'              => ['nullable', 'array'],
            'attribute_ids.*'            => ['integer'],
            'dungeon_start_id'           => ['nullable', 'integer'],
            'pull_gradient'              => ['nullable', 'string', 'max:2000'],
            'pull_gradient_apply_always' => ['nullable', 'boolean'],
            'party'                      => ['nullable', 'array', sprintf('max:%d', self::PARTY_SIZE)],
            'party.*.class_id'           => ['nullable', 'integer', 'exists:character_classes,id'],
            'party.*.specialization_id'  => ['nullable', 'integer', 'exists:character_class_specializations,id'],
            'party.*.race_id'            => ['nullable', 'integer', 'exists:character_races,id'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'edit');

        if (isset($validated['dungeon_start_id']) &&
            !$dungeonRoute->mappingVersion->dungeonStarts()->whereKey($validated['dungeon_start_id'])->exists()) {
            throw new McpToolException(__('mcp.error.unknown_dungeon_start', ['start' => $validated['dungeon_start_id']]));
        }

        $party = $validated['party'] ?? null;
        if ($party !== null) {
            $this->ensureSpecializationsMatchClasses($party);
        }

        // DungeonRouteSaveService keeps the current value of most settings a save leaves out, but resets these four - the
        // editor's settings tab always sends them, so the current values go in for whatever the call does not change
        $formData = array_filter([
            'dungeon_route_title'        => $validated['title'] ?? null,
            'dungeon_route_description'  => $validated['description'] ?? null,
            'dungeon_route_level'        => isset($validated['level_min']) ? sprintf('%d;%d', $validated['level_min'], $validated['level_max']) : null,
            'faction_id'                 => isset($validated['faction']) ? Faction::ALL[$validated['faction']] : $dungeonRoute->faction_id,
            'seasonal_index'             => isset($validated['seasonal_index']) ? [$validated['seasonal_index']] : null,
            'route_select_affixes'       => isset($validated['affix_group_ids']) ? array_map('strval', $validated['affix_group_ids']) : null,
            'attributes'                 => $validated['attribute_ids'] ?? null,
            'class'                      => $party === null ? null : $this->partyColumn($party, 'class_id'),
            'specialization'             => $party === null ? null : $this->partyColumn($party, 'specialization_id'),
            'race'                       => $party === null ? null : $this->partyColumn($party, 'race_id'),
            'dungeon_start_id'           => $validated['dungeon_start_id'] ?? $dungeonRoute->dungeon_start_id,
            'dungeon_difficulty'         => $dungeonRoute->dungeon_difficulty,
            'pull_gradient'              => $validated['pull_gradient'] ?? $dungeonRoute->pull_gradient,
            'pull_gradient_apply_always' => (int)($validated['pull_gradient_apply_always'] ?? $dungeonRoute->pull_gradient_apply_always),
            // The faction rule reads the route's dungeon off the request
            'dungeon_id' => $dungeonRoute->dungeon_id,
        ], static fn($value) => $value !== null);

        $validatedFormData = $this->validateWithFormRequest(AjaxDungeonRouteSubmitFormRequest::class, $user, $formData);
        // Never move the route to another team or flag it a demo from an AI client
        unset($validatedFormData['team_id'], $validatedFormData['demo'], $validatedFormData['template']);

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        $beforeDungeonRoute = clone $dungeonRoute;
        if (!$this->dungeonRouteSaveService->save($dungeonRoute, $validatedFormData)) {
            throw new McpToolException(__('mcp.error.unable_to_save_route'));
        }

        $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);

        return Response::structured($this->dungeonRouteFormatter->formatRoute($dungeonRoute->fresh()));
    }

    /**
     * @param  array<int, array<string, int|null>> $party
     * @return list<int>
     */
    private function partyColumn(array $party, string $column): array
    {
        return array_values(array_filter(array_map(static fn(array $member) => $member[$column] ?? null, $party), static fn($id) => $id !== null));
    }

    /**
     * @param array<int, array<string, int|null>> $party
     *
     * @throws McpToolException
     */
    private function ensureSpecializationsMatchClasses(array $party): void
    {
        foreach ($party as $position => $member) {
            if (!isset($member['class_id'], $member['specialization_id'])) {
                continue;
            }

            $specializationClassId = CharacterClassSpecialization::query()->whereKey($member['specialization_id'])->value('character_class_id');
            if ((int)$specializationClassId !== (int)$member['class_id']) {
                throw new McpToolException(__('mcp.error.specialization_not_of_class', ['member' => $position + 1]));
            }
        }
    }
}
