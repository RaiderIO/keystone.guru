<?php

namespace App;

use App\Email\CustomPasswordResetEmail;
use App\Models\DungeonRoute;
use App\Models\GameServerRegion;
use App\Models\PaidTier;
use App\Models\PatreonData;
use App\Models\Tags\Tag;
use App\Models\Team;
use App\Models\UserReport;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laratrust\Traits\LaratrustUserTrait;

/**
 * @property int $id
 * @property int $game_server_region_id
 * @property string $timezone
 * @property string $name
 * @property string $email
 * @property string $echo_color
 * @property boolean $echo_anonymous
 * @property string $password
 * @property boolean $legal_agreed
 * @property int $legal_agreed_ms
 * @property boolean $analytics_cookie_opt_out
 * @property boolean $adsense_no_personalized_ads
 * @property boolean $changed_username
 * @property PatreonData $patreondata
 * @property GameServerRegion $gameserverregion
 *
 * @property DungeonRoute[]|Collection $dungeonroutes
 * @property UserReport[]|Collection $reports
 * @property Team[]|Collection $teams
 * @property Role[]|Collection $roles
 * @property Tag[]|Collection $tags
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use LaratrustUserTrait;
    use Notifiable;

    /**
     * @var string Have to specify connection explicitly so that Tracker still works (has its own DB)
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'oauth_id', 'game_server_region_id', 'name', 'email', 'echo_color', 'password', 'legal_agreed', 'legal_agreed_ms'
    ];

    /**
     * The attributes that should be visible for outsiders.
     *
     * @var array
     */
    protected $visible = [
        'id', 'name', 'echo_color'
    ];


    /**
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * @return HasMany
     */
    function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute', 'author_id');
    }

    /**
     * @return HasMany
     */
    function reports()
    {
        return $this->hasMany('App\Models\UserReport');
    }

    /**
     * @return HasOne
     */
    function patreondata()
    {
        return $this->hasOne('App\Models\PatreonData');
    }

    /**
     * @return BelongsTo
     */
    function gameserverregion()
    {
        // Don't know why it won't work without the foreign key specified..
        return $this->belongsTo('App\Models\GameServerRegion', 'game_server_region_id');
    }

    /**
     * @return BelongsToMany
     */
    function teams()
    {
        return $this->belongsToMany('App\Models\Team', 'team_users');
    }

    /**
     * @return HasMany
     */
    function tags()
    {
        return $this->hasMany('App\Models\Tags\Tag');
    }

    /**
     * Checks if this user has registered using OAuth or not.
     *
     * @return bool
     */
    public function isOAuth()
    {
        return empty($this->password);
    }

    /**
     * Checks if this user has paid for a certain tier one way or the other.
     *
     * @param $name
     * @return bool
     */
    function hasPaidTier($name)
    {
        // True for all admins
        $result = $this->hasRole('admin');

        // If we weren't an admin, check patreon data
        if (!$result && $this->patreondata !== null) {
            foreach ($this->patreondata->paidtiers as $tier) {
                if ($tier->name === $name) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get a list of tiers that this User has access to.
     *
     * @return Collection
     */
    function getPaidTiers()
    {
        // Admins have all paid tiers
        if ($this->hasRole('admin')) {
            $result = PaidTier::all()->pluck(['name']);
        } else if (isset($this->patreondata)) {
            $result = $this->patreondata->paidtiers->pluck(['name']);
        } else {
            $result = collect();
        }

        return $result;
    }

    /**
     * Checks if this user can create a dungeon route or not (based on free account limits)
     */
    function canCreateDungeonRoute()
    {
        return DungeonRoute::where('author_id', $this->id)->count() < config('keystoneguru.registered_user_dungeonroute_limit') ||
            $this->hasPaidTier(PaidTier::UNLIMITED_DUNGEONROUTES);
    }

    /**
     * Get the amount of routes a user may still create.
     *
     * NOTE: Will be inaccurate if the user is a Patron. Just don't call this function then.
     * @return mixed
     */
    function getRemainingRouteCount()
    {
        return max(0,
            config('keystoneguru.registered_user_dungeonroute_limit') - DungeonRoute::where('author_id', $this->id)->count()
        );
    }

    /**
     * Sends the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomPasswordResetEmail($token));
    }

    /**
     * Gets a list of consequences that will happen when this user tries to delete their account.
     */
    public function getDeleteConsequences()
    {
        $teams = ['teams' => []];
        foreach ($this->teams as $team) {
            /** @var $team Team */
            $teams['teams'][$team->name] = [
                'result'    => $team->members->count() === 1 ? 'deleted' : 'new_owner',
                'new_owner' => $team->getNewAdminUponAdminAccountDeletion($this)
            ];
        }

        return array_merge($teams, [
            'patreon'       => [
                'unlinked' => $this->patreondata !== null
            ],
            'dungeonroutes' => [
                'delete_count' => ($this->dungeonroutes->count() - $this->dungeonroutes()->isSandbox()->count())
            ],
            'reports'       => [
                'delete_count' => ($this->reports()->where('status', 0)->count())
            ]
        ]);
    }

    public static function boot()
    {
        parent::boot();

        // Delete user properly if it gets deleted
        static::deleting(function ($item)
        {
            /** @var $item User */
            $item->dungeonroutes()->delete();
            $item->reports()->delete();

            $item->patreondata()->delete();

            foreach ($item->teams as $team) {
                /** @var $team Team */
                $newAdmin = $team->getNewAdminUponAdminAccountDeletion($item);
                if ($newAdmin !== null) {
                    // Appoint someone else admin
                    $team->changeRole(User::find($newAdmin->id), 'admin');
                    // Remove ourselves from the team
                    $team->removeMember($item);
                } else {
                    $team->delete();
                }
            }
            return true;
        });
    }
}
