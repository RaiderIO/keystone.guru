<?php

namespace App\Models\SimulationCraft;

use App\Http\Requests\DungeonRoute\APISimulateFormRequest;
use App\Models\DungeonRoute;
use App\Models\Traits\GeneratesPublicKey;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_key
 * @property int $dungeon_route_id
 * @property int $user_id
 * @property int $key_level
 * @property string $shrouded_bounty_type
 * @property string $affix
 * @property bool $bloodlust
 * @property bool $arcane_intellect
 * @property bool $power_word_fortitude
 * @property bool $battle_shout
 * @property bool $mystic_touch
 * @property bool $chaos_brand
 * @property float $skill_loss_percent
 * @property float $hp_percent
 *
 * @property DungeonRoute $dungeonroute
 *
 * @package App\Models\SimulationCraft
 * @author Wouter
 * @since 27/08/2022
 */
class SimulationCraftRaidEventsOptions extends Model
{
    use GeneratesPublicKey;

    public $timestamps = true;
    protected $fillable = [
        'key_level',
        'shrouded_bounty_type',
        'affix',
        'bloodlust',
        'arcane_intellect',
        'power_word_fortitude',
        'battle_shout',
        'mystic_touch',
        'chaos_brand',
        'skill_loss_percent',
        'hp_percent',
    ];
    protected $with = ['dungeonroute'];

    public const SHROUDED_BOUNTY_TYPE_CRIT    = 'crit';
    public const SHROUDED_BOUNTY_TYPE_HASTE   = 'haste';
    public const SHROUDED_BOUNTY_TYPE_MASTERY = 'mastery';
    public const SHROUDED_BOUNTY_TYPE_VERS    = 'vers';

    public const ALL_SHROUDED_BOUNTY_TYPES = [
        self::SHROUDED_BOUNTY_TYPE_CRIT,
        self::SHROUDED_BOUNTY_TYPE_HASTE,
        self::SHROUDED_BOUNTY_TYPE_MASTERY,
        self::SHROUDED_BOUNTY_TYPE_VERS,
    ];

    public const AFFIX_FORTIFIED  = 'fortified';
    public const AFFIX_TYRANNICAL = 'tyrannical';

    public const ALL_AFFIXES = [
        self::AFFIX_FORTIFIED,
        self::AFFIX_TYRANNICAL,
    ];

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param APISimulateFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @return SimulationCraftRaidEventsOptions
     */
    public static function fromRequest(APISimulateFormRequest $request, DungeonRoute $dungeonRoute): SimulationCraftRaidEventsOptions
    {
        $result               = new SimulationCraftRaidEventsOptions(array_merge($request->validated(), [
            'public_key'       => 'asdfasdf', // self::generateRandomPublicKey(),
            'user_id'          => \Auth::id(),
            'dungeon_route_id' => $dungeonRoute->id,
        ]));
        $result->dungeonroute = $dungeonRoute;
        return $result;
    }
}
