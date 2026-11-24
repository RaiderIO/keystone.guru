<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Support\SpellFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\Spell\SpellCategory;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(<<<'TEXT'
    Lists the player spells keystone.guru tracks for one class (class keys as in get_route_options, or "general"
    for spells such as Bloodlust drums), with their description, school, dispel type, mechanic, cast time and aura
    duration in milliseconds. Spells are stored per class, not per specialization, and no cooldown duration is
    stored; fields that are unknown are left out. selectable_in_pull marks the spells update_pull accepts.
    TEXT)]
#[IsReadOnly]
#[IsIdempotent]
class ListClassSpellsTool extends McpTool
{
    protected string $name = 'list_class_spells';

    public function __construct(
        private readonly SpellFormatter           $spellFormatter,
        private readonly SpellRepositoryInterface $spellRepository,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRepositoryInterface                $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'class'               => $schema->string()->enum(self::getClassKeys())->description('The class key, or general.')->required(),
            'include_pvp_talents' => $schema->boolean()->description('Also list PvP talents. Defaults to false.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'class'               => ['required', 'string', Rule::in(self::getClassKeys())],
            'include_pvp_talents' => ['nullable', 'boolean'],
        ]);

        $spells = $this->spellRepository->getByCategory(
            SpellCategory::from($validated['class'])->translationKey(),
            (bool)($validated['include_pvp_talents'] ?? false),
            SpellFormatter::RELATIONS,
        );

        return Response::structured([
            'class'  => $validated['class'],
            'spells' => $spells->map($this->spellFormatter->format(...))->values()->all(),
        ]);
    }

    /**
     * @return list<string>
     */
    private static function getClassKeys(): array
    {
        return array_values(array_filter(SpellCategory::values(), static fn(string $value) => $value !== SpellCategory::Unknown->value));
    }
}
