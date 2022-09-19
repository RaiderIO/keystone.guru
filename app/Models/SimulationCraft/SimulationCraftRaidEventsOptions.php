<?php

namespace App\Models\SimulationCraft;

use App\Http\Requests\DungeonRoute\APISimulateFormRequest;
use App\Models\DungeonRoute;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Traits\GeneratesPublicKey;
use App\User;
use Auth;
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
 * @property bool $bloodlust Override to say yes/no to Bloodlust/Heroism being available.
 * @property bool $arcane_intellect
 * @property bool $power_word_fortitude
 * @property bool $battle_shout
 * @property bool $mystic_touch
 * @property bool $chaos_brand
 * @property float $hp_percent
 * @property float $ranged_pull_compensation_yards Premium: the amount of yards that are 'free' between pulls because you
 * don't have to always walk from center of previous pull to center of next pull. This reduces the delay between pulls making the sims more accurate
 * @property bool $use_mounts Premium: yes to enable mount usage to further reduce delay between pulls
 * @property string $simulate_bloodlust_per_pull The killzone IDs, comma separated, that Bloodlust/Heroism should be used on
 *
 * @property DungeonRoute $dungeonroute
 *
 * @package App\Models\SimulationCraft
 * @author Wouter
 * @since 27/08/2022
 *
 * @mixin \Eloquent
 */
class SimulationCraftRaidEventsOptions extends Model
{
    use GeneratesPublicKey;

    public $timestamps = true;
    protected $fillable = [
        'public_key',
        'dungeon_route_id',
        'user_id',
        'key_level',
        'shrouded_bounty_type',
        'affix',
        'bloodlust',
        'arcane_intellect',
        'power_word_fortitude',
        'battle_shout',
        'mystic_touch',
        'chaos_brand',
        'hp_percent',
        'simulate_bloodlust_per_pull',
        'ranged_pull_compensation_yards',
        'use_mounts',
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
        $hasAdvancedSimulation = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ADVANCED_SIMULATION);

        $validated = $request->validated();
        $bloodLustPerPull = implode(',', $validated['simulate_bloodlust_per_pull']);
        unset($validated['simulate_bloodlust_per_pull']);

        $result               = SimulationCraftRaidEventsOptions::create(array_merge($validated, [
            'public_key'                     => self::generateRandomPublicKey(),
            'user_id'                        => Auth::id(),
            'dungeon_route_id'               => $dungeonRoute->id,
            'simulate_bloodlust_per_pull'    => $bloodLustPerPull,
            // Set the ranged pull compensation, if the user is allowed to set it. Otherwise, reduce the value to 0
            'ranged_pull_compensation_yards' => $hasAdvancedSimulation ? (int)$request->get('ranged_pull_compensation_yards') : 0,
            'use_mounts'                     => $hasAdvancedSimulation ? (int)$request->get('use_mounts') : 0,
        ]));
        $result->dungeonroute = $dungeonRoute;
        return $result;
    }
}
