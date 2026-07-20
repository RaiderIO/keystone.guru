<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogParseFailure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @OA\Schema(schema="CombatLogParseFailure")
 * @OA\Property(property="id", type="integer", example=1)
 * @OA\Property(property="runId", type="integer", example=123456789)
 * @OA\Property(property="seasonId", type="integer", nullable=true, example=27)
 * @OA\Property(property="combatLogVersion", type="integer", nullable=true, example=22012000005)
 * @OA\Property(property="lineNumber", type="integer", nullable=true, example=12345)
 * @OA\Property(property="rawLine", type="string", nullable=true, example="SPELL_DAMAGE,Player-1084-0B4087DE,...")
 * @OA\Property(property="message", type="string", example="Unbalanced quotes in string ...")
 * @OA\Property(property="exceptionClass", type="string", example="InvalidArgumentException")
 * @OA\Property(property="createdAt", type="string", format="date-time", example="2026-07-20T00:00:00Z")
 *
 * @mixin CombatLogParseFailure
 */
class CombatLogParseFailureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'runId'            => $this->run_id,
            'seasonId'         => $this->season_id,
            'combatLogVersion' => $this->combat_log_version,
            'lineNumber'       => $this->line_number,
            'rawLine'          => $this->raw_line,
            'message'          => $this->message,
            'exceptionClass'   => $this->exception_class,
            'createdAt'        => $this->created_at,
        ];
    }
}
