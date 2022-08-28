<?php

namespace App\Models\SimulationCraft;

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
 * @property float $skill
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

    public const SHROUDED_BOUNTY_TYPE_HASTE   = 'haste';
    public const SHROUDED_BOUNTY_TYPE_CRIT    = 'crit';
    public const SHROUDED_BOUNTY_TYPE_MASTERY = 'mastery';
    public const SHROUDED_BOUNTY_TYPE_VERS    = 'vers';

    public const AFFIX_FORTIFIED  = 'fortified';
    public const AFFIX_TYRANNICAL = 'tyrannical';


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

}
