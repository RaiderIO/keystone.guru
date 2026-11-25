<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\SpellFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\Spell\SpellCategory;
use App\Models\User;
use App\Repositories\Interfaces\CharacterClassSpecializationRepositoryInterface;
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
    duration in milliseconds. cooldown_seconds is the base cooldown without talents (for a spell with charges, the
    time one charge takes to return), charges how many it holds, and specializations the specializations that can
    cast it; each of these is null when unknown, never guessed. Give specialization_id (from get_route_options) to
    list only the spells that specialization can cast, plus those whose specializations are unknown. Other fields
    that are unknown are left out. selectable_in_pull marks the spells update_pull accepts.
    TEXT)]
#[IsReadOnly]
#[IsIdempotent]
class ListClassSpellsTool extends McpTool
{
    protected string $name = 'list_class_spells';

    public function __construct(
        private readonly SpellFormatter                                  $spellFormatter,
        private readonly SpellRepositoryInterface                        $spellRepository,
        private readonly CharacterClassSpecializationRepositoryInterface $characterClassSpecializationRepository,
        DungeonRouteRepositoryInterface                                  $dungeonRouteRepository,
        DungeonRepositoryInterface                                       $dungeonRepository,
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
            'specialization_id'   => $schema->integer()->description('A specialization id from get_route_options; it must be of the class.'),
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
            'specialization_id'   => ['nullable', 'integer', 'exists:character_class_specializations,id'],
        ]);

        $specialization = isset($validated['specialization_id'])
            ? $this->characterClassSpecializationRepository->findOrFail((int)$validated['specialization_id'])
            : null;

        if ($specialization !== null
            && $validated['class'] !== SpellCategory::General->value
            && $specialization->class->key !== $validated['class']) {
            throw new McpToolException(__('mcp.error.specialization_not_of_spell_class', [
                'specialization' => $specialization->id,
                'class'          => $validated['class'],
            ]));
        }

        $spells = $this->spellRepository->getByCategory(
            SpellCategory::from($validated['class'])->translationKey(),
            (bool)($validated['include_pvp_talents'] ?? false),
            SpellFormatter::RELATIONS,
            $specialization?->id,
        );

        return Response::structured([
            'class'             => $validated['class'],
            'specialization_id' => $specialization?->id,
            'spells'            => $spells->map($this->spellFormatter->format(...))->values()->all(),
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
