<?php

namespace App\Http\Requests\OverpulledEnemy;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class OverpulledEnemyFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string|Exists>|string> */
    public function rules(): array
    {
        return [
            'kill_zone_id' => [
                'required',
                'integer',
                Rule::exists(KillZone::class, 'id')->where('dungeon_route_id', $this->getDungeonRoute()->id),
            ],
            'enemy_ids'   => 'required|array',
            'enemy_ids.*' => 'numeric',
            'no_result'   => 'nullable|bool',
        ];
    }

    public function getKillZone(): KillZone
    {
        return once(fn(): KillZone => KillZone::query()
            ->where('dungeon_route_id', $this->getDungeonRoute()->id)
            ->findOrFail($this->validated('kill_zone_id')));
    }

    private function getDungeonRoute(): DungeonRoute
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $this->route('dungeonRoute');

        return $dungeonRoute;
    }
}
