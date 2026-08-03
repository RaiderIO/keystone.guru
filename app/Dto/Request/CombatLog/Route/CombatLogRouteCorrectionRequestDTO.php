<?php

namespace App\Dto\Request\CombatLog\Route;

use Illuminate\Support\Collection;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteCorrectionRequest")
 * @OA\Property(property="metadata",type="object",ref="#/components/schemas/CombatLogRouteMetadata")
 * @OA\Property(property="settings",type="object",ref="#/components/schemas/CombatLogRouteSettings")
 * @OA\Property(property="challengeMode",type="object",ref="#/components/schemas/CombatLogRouteChallengeMode")
 * @OA\Property(property="npcs",type="array",items={"$ref":"#/components/schemas/CombatLogRouteNpcCorrection"})
 * @OA\Property(property="spells",type="array",items={"$ref":"#/components/schemas/CombatLogRouteSpellCorrection"}, nullable=true)
 * @OA\Property(property="playerDeaths",type="array",items={"$ref":"#/components/schemas/CombatLogRoutePlayerDeathCorrection"}, nullable=true)
 *
 * @property Collection<int, CombatLogRouteNpcCorrectionRequestDTO>         $npcs
 * @property Collection<int, CombatLogRouteSpellCorrectionRequestDTO>       $spells
 * @property Collection<int, CombatLogRoutePlayerDeathCorrectionRequestDTO> $playerDeaths
 */
class CombatLogRouteCorrectionRequestDTO extends CombatLogRouteRequestDTO
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    #[Override]
    public static function getCollectionItemType(string $key): ?string
    {
        return match ($key) {
            'npcs'         => CombatLogRouteNpcCorrectionRequestDTO::class,
            'spells'       => CombatLogRouteSpellCorrectionRequestDTO::class,
            'playerDeaths' => CombatLogRoutePlayerDeathCorrectionRequestDTO::class,
            default        => null,
        };
    }
}
