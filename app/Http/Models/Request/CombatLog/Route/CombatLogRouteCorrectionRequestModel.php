<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Support\Collection;

/**
 * @OA\Schema(schema="CombatLogRouteCorrectionRequest")
 * @OA\Property(property="metadata",type="object",ref="#/components/schemas/CombatLogRouteMetadata")
 * @OA\Property(property="settings",type="object",ref="#/components/schemas/CombatLogRouteSettings")
 * @OA\Property(property="challengeMode",type="object",ref="#/components/schemas/CombatLogRouteChallengeMode")
 * @OA\Property(property="npcs",type="array",items={"$ref":"#/components/schemas/CombatLogRouteNpcCorrection"})
 * @OA\Property(property="spells",type="array",items={"$ref":"#/components/schemas/CombatLogRouteSpellCorrection"}, nullable=true)
 * @OA\Property(property="playerDeaths",type="array",items={"$ref":"#/components/schemas/CombatLogRoutePlayerDeathCorrection"}, nullable=true)
 *
 * @property Collection<CombatLogRouteNpcCorrectionRequestModel>         $npcs
 * @property Collection<CombatLogRouteSpellCorrectionRequestModel>       $spells
 * @property Collection<CombatLogRoutePlayerDeathCorrectionRequestModel> $playerDeaths
 */
class CombatLogRouteCorrectionRequestModel extends CombatLogRouteRequestModel
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public static function getCollectionItemType(string $key): ?string
    {
        return match ($key) {
            'npcs'         => CombatLogRouteNpcCorrectionRequestModel::class,
            'spells'       => CombatLogRouteSpellCorrectionRequestModel::class,
            'playerDeaths' => CombatLogRoutePlayerDeathCorrectionRequestModel::class,
            default        => null,
        };
    }
}
