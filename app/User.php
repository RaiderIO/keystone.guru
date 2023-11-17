<?php

namespace App;

use App\Email\CustomPasswordResetEmail;
use App\Models\DungeonRoute;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\Tags\Tag;
use App\Models\Team;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\Traits\HasIconFile;
use App\Models\UserReport;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laratrust\Traits\LaratrustUserTrait;

/**
 * @property int                       $id
 * @property string                    $public_key
 * @property int                       $game_server_region_id
 * @property int                       $patreon_user_link_id
 * @property int                       $game_version_id
 * @property string                    $name
 * @property string                    $initials The initials (two letters) of a user so we can display it as the connected user in case of no avatar
 * @property string                    $email
 * @property string                    $locale
 * @property string                    $theme
 * @property string                    $echo_color
 * @property boolean                   $echo_anonymous
 * @property boolean                   $changed_username
 * @property string                    $timezone
 * @property string                    $map_facade_style
 * @property string                    $password
 * @property string                    $raw_patreon_response_data
 * @property boolean                   $legal_agreed
 * @property int                       $legal_agreed_ms
 * @property boolean                   $analytics_cookie_opt_out
 *
 * @property PatreonUserLink           $patreonUserLink
 * @property GameServerRegion          $gameServerRegion
 * @property GameVersion               $gameVersion
 * @property PatreonAdFreeGiveaway     $patreonAdFreeGiveaway
 *
 * @property boolean                   $is_admin
 *
 * @property DungeonRoute[]|Collection $dungeonRoutes
 * @property UserReport[]|Collection   $reports
 * @property Team[]|Collection         $teams
 * @property Role[]|Collection         $roles
 * @property Tag[]|Collection          $tags
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use HasIconFile;
    use LaratrustUserTrait;
    use Notifiable;
    use GeneratesPublicKey;

    public const MAP_FACADE_STYLE_SPLIT_FLOORS = 'split_floors';
    public const MAP_FACADE_STYLE_FACADE       = 'facade';

    public const MAP_FACADE_STYLE_ALL = [
        self::MAP_FACADE_STYLE_SPLIT_FLOORS,
        self::MAP_FACADE_STYLE_FACADE,
    ];

    public const DEFAULT_MAP_FACADE_STYLE = 'split_floors';

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
        'id',
        'game_server_region_id',
        'patreon_user_link_id',
        'game_version_id',
        'public_key',
        'oauth_id',
        'name',
        'email',
        'echo_color',
        'password',
        'legal_agreed',
        'legal_agreed_ms',
    ];

    /**
     * The attributes that should be visible for outsiders.
     *
     * @var array
     */
    protected $visible = [
        'id', 'public_key', 'name', 'echo_color',
    ];

    protected $appends = [
        'initials',
    ];

    protected $with = ['iconfile', 'patreonUserLink', 'gameVersion'];

    /**
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

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
    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class, 'author_id');
    }

    /**
     * @return HasMany
     */
    public function reports(): HasMany
    {
        return $this->hasMany(UserReport::class);
    }

    /**
     * @return HasOne
     */
    public function patreonUserLink(): HasOne
    {
        return $this->hasOne(PatreonUserLink::class);
    }

    /**
     * @return BelongsTo
     */
    public function gameServerRegion(): BelongsTo
    {
        return $this->belongsTo(GameServerRegion::class);
    }

    /**
     * @return BelongsTo
     */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    /**
     * @return BelongsToMany
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_users');
    }

    /**
     * @return HasOne
     */
    public function patreonAdFreeGiveaway(): HasOne
    {
        return $this->hasOne(PatreonAdFreeGiveaway::class, 'receiver_user_id');
    }

    /**
     * @param int|null $categoryId
     *
     * @return HasMany|Tag
     */
    public function tags(?int $categoryId = null): HasMany
    {
        $result = $this->hasMany(Tag::class);

        if ($categoryId !== null) {
            $result->where('tag_category_id', $categoryId);
        }

        return $result;
    }

    /**
     * Checks if this user has registered using OAuth or not.
     *
     * @return bool
     */
    public function isOAuth(): bool
    {
        return empty($this->password);
    }

    /**
     * Checks if this user has paid for a certain tier one way or the other.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasPatreonBenefit(string $key): bool
    {
        // True for all admins
        $result = $this->hasRole('admin');

        // If we weren't an admin, check patreon data
        if (!$result && $this->patreonUserLink !== null && isset(PatreonBenefit::ALL[$key])) {
            $result = $this->patreonUserLink->patreonbenefits()->where('patreon_benefits.id', PatreonBenefit::ALL[$key])->exists();
        }

        return $result;
    }

    /**
     * Get a list of tiers that this User has access to.
     *
     * @return Collection
     */
    public function getPatreonBenefits(): Collection
    {
        // Admins have all patreon benefits
        if ($this->hasRole('admin')) {
            $result = collect(array_keys(PatreonBenefit::ALL));
        } else if (isset($this->patreonUserLink)) {
            $result = $this->patreonUserLink->patreonbenefits->pluck(['key']);
        } else {
            $result = collect();
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function hasAdFreeGiveaway(): bool
    {
        return PatreonAdFreeGiveaway::where('receiver_user_id', $this->id)->exists();
    }

    /**
     * @return User|null
     */
    public function getAdFreeGiveawayUser(): ?User
    {
        /** @var User|null $user */
        $user = PatreonAdFreeGiveaway::where('receiver_user_id', $this->id)->first()->giver;

        return $user;
    }

    /**
     * Checks if this user can create a dungeon route or not (based on free account limits)
     */
    public function canCreateDungeonRoute(): bool
    {
        return DungeonRoute::where('author_id', $this->id)->count() < config('keystoneguru.registered_user_dungeonroute_limit') ||
            $this->hasPatreonBenefit(PatreonBenefit::UNLIMITED_DUNGEONROUTES);
    }

    /**
     * Get the amount of routes a user may still create.
     *
     * NOTE: Will be inaccurate if the user is a Patron. Just don't call this function then.
     * @return int
     */
    public function getRemainingRouteCount(): int
    {
        return (int)max(0,
            config('keystoneguru.registered_user_dungeonroute_limit') - DungeonRoute::where('author_id', $this->id)->count()
        );
    }

    /**
     * Sends the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomPasswordResetEmail($token));
    }

    /**
     * Gets a list of consequences that will happen when this user tries to delete their account.
     */
    public function getDeleteConsequences(): array
    {
        $teams = ['teams' => []];
        foreach ($this->teams as $team) {
            try {
                $newOwner = $team->getNewAdminUponAdminAccountDeletion($this);
            } catch (Exception $exception) {
                $newOwner = null;
            }
            /** @var $team Team */
            $teams['teams'][$team->name] = [
                'result'    => $team->members()->count() === 1 ? 'deleted' : 'new_owner',
                'new_owner' => $newOwner,
            ];
        }

        return array_merge($teams, [
            'patreon'       => [
                'unlinked' => $this->patreonUserLink !== null,
            ],
            'dungeonroutes' => [
                'delete_count' => ($this->dungeonRoutes()->count() - $this->dungeonRoutes()->isSandbox()->count()),
            ],
            'reports'       => [
                'delete_count' => ($this->reports()->where('status', 0)->count()),
            ],
        ]);
    }

    /**
     * @return string
     */
    public static function getCurrentUserMapFacadeStyle(): string
    {
        return optional(Auth::user())->map_facade_style ?? $_COOKIE['map_facade_style'] ?? User::DEFAULT_MAP_FACADE_STYLE;
    }

    public static function boot()
    {
        parent::boot();

        // Delete user properly if it gets deleted
        static::deleting(function (User $user) {
            $user->dungeonRoutes()->delete();
            $user->reports()->delete();

            $user->patreonUserLink()->delete();

            foreach ($user->teams as $team) {
                // Remove ourselves from the team
                $team->removeMember($user);

                /** @var $team Team */
                if (!$team->isUserAdmin($user)) {
                    continue;
                }

                /** @var $team Team */
                try {
                    $newAdmin = $team->getNewAdminUponAdminAccountDeletion($user);
                    if ($newAdmin !== null) {
                        // Appoint someone else admin
                        $team->changeRole(User::find($newAdmin->id), 'admin');
                    } else {
                        // There's no new admin to be appointed - delete the team instead
                        $team->delete();
                    }
                } catch (Exception $exception) {
                    logger()->error($exception->getMessage());
                }
            }

            return true;
        });
    }
}
