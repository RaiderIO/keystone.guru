<?php

namespace App\Models\SimulationCraft;

use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSimulateFormRequest;
use App\Models\Affix;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Traits\BitMasks;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\User;
use Auth;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Random\RandomException;

/**
 * @property int    $id
 * @property string $public_key
 * @property int    $dungeon_route_id
 * @property int    $user_id
 * @property int    $key_level
 * @property string $affix                          Comma separated list of affixes
 * @property int    $raid_buffs_mask
 * @property float  $hp_percent
 * @property float  $ranged_pull_compensation_yards Premium: the amount of yards that are 'free' between pulls because you
 *                                                  don't have to always walk from center of previous pull to center of next pull. This reduces the delay between pulls making the sims more accurate
 * @property bool   $use_mounts                     Premium: yes to enable mount usage to further reduce delay between pulls
 * @property string $simulate_bloodlust_per_pull    The killzone IDs, comma separated, that Bloodlust/Heroism should be used on
 *
 * @property DungeonRoute $dungeonRoute
 *
 * @author Wouter
 *
 * @since 27/08/2022
 *
 * @mixin Eloquent
 */
class SimulationCraftRaidEventsOptions extends Model
{
    use GeneratesPublicKey, BitMasks;

    public $timestamps = true;

    protected $fillable = [
        'public_key',
        'dungeon_route_id',
        'user_id',
        'key_level',
        'affix',
        'raid_buffs_mask',
        'hp_percent',
        'simulate_bloodlust_per_pull',
        'ranged_pull_compensation_yards',
        'use_mounts',
    ];

    protected $with = ['dungeonRoute'];

    protected function casts(): array
    {
        return [
            'id'                             => 'int',
            'dungeon_route_id'               => 'int',
            'user_id'                        => 'int',
            'key_level'                      => 'int',
            'raid_buffs_mask'                => 'int',
            'hp_percent'                     => 'float',
            'ranged_pull_compensation_yards' => 'int',
            'use_mounts'                     => 'bool',
        ];
    }

    public const AFFIX_FORTIFIED  = 'fortified';
    public const AFFIX_TYRANNICAL = 'tyrannical';

    public const ALL_AFFIXES = [
        self::AFFIX_FORTIFIED,
        self::AFFIX_TYRANNICAL,
    ];

    /** @return BelongsTo<DungeonRoute, $this> */
    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addRaidBuff(SimulationCraftRaidBuffs $raidBuff): void
    {
        $this->raid_buffs_mask = $this->bitMaskAdd($this->raid_buffs_mask, $raidBuff->value);
    }

    public function removeRaidBuff(SimulationCraftRaidBuffs $raidBuff): void
    {
        $this->raid_buffs_mask = $this->bitMaskRemove($this->raid_buffs_mask, $raidBuff->value);
    }

    public function hasRaidBuff(SimulationCraftRaidBuffs $raidBuff): bool
    {
        return $this->bitMaskHasValue($this->raid_buffs_mask, $raidBuff->value);
    }

    public function hasAffix(string $affix): bool
    {
        return in_array($affix, explode(',', $this->affix));
    }

    /** @return array<int, string> */
    public function getAffixes(): array
    {
        $affixes        = [];
        $optionsAffixes = explode(',', $this->affix);
        if (in_array(SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED, $optionsAffixes)) {
            $affixes[] = Affix::AFFIX_FORTIFIED;
        }
        if (in_array(SimulationCraftRaidEventsOptions::AFFIX_TYRANNICAL, $optionsAffixes)) {
            $affixes[] = Affix::AFFIX_TYRANNICAL;
        }

        return $affixes;
    }

    /**
     * @throws RandomException
     */
    public static function fromRequest(
        AjaxDungeonRouteSimulateFormRequest $request,
        DungeonRoute                        $dungeonRoute,
    ): SimulationCraftRaidEventsOptions {
        $hasAdvancedSimulation = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ADVANCED_SIMULATION);

        $validated = $request->validated();

        $bloodLustPerPull = implode(',', $validated['simulate_bloodlust_per_pull'] ?? []);
        unset($validated['simulate_bloodlust_per_pull']);

        $affixesCsv = implode(',', $validated['affix'] ?? []);
        unset($validated['affix']);

        $result = SimulationCraftRaidEventsOptions::create(array_merge($validated, [
            'public_key'                  => self::generateRandomPublicKey(),
            'user_id'                     => Auth::id(),
            'dungeon_route_id'            => $dungeonRoute->id,
            'affix'                       => $affixesCsv,
            'simulate_bloodlust_per_pull' => $bloodLustPerPull,
            // Set the ranged pull compensation, if the user is allowed to set it. Otherwise, reduce the value to 0
            'ranged_pull_compensation_yards' => $hasAdvancedSimulation ? (int)$request->get('ranged_pull_compensation_yards') : 0,
            'use_mounts'                     => $hasAdvancedSimulation ? (int)$request->get('use_mounts') : 0,
        ]));
        $result->dungeonRoute = $dungeonRoute;

        return $result;
    }
}
