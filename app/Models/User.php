<?php

namespace App\Models;

use App\Email\CustomPasswordResetEmail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\Tags\Tag;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\HasTags;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Override;

/**
 * @property int    $id
 * @property string $public_key
 * @property int    $game_server_region_id
 * @property int    $patreon_user_link_id
 * @property int    $game_version_id
 * @property int    $dungeon_id                The dungeon context this user is in.
 * @property string $name
 * @property string $initials                  The initials (two letters) of a user so we can display it as the connected user in case of no avatar
 * @property string $email
 * @property string $locale
 * @property string $theme
 * @property string $echo_color
 * @property bool   $echo_anonymous
 * @property bool   $changed_username
 * @property string $timezone
 * @property string $map_facade_style
 * @property int    $kill_zone_path_weight
 * @property string $password
 * @property string $raw_patreon_response_data
 * @property bool   $legal_agreed
 * @property int    $legal_agreed_ms
 * @property bool   $analytics_cookie_opt_out
 *
 * @property PatreonUserLink|null       $patreonUserLink
 * @property GameServerRegion|null      $gameServerRegion
 * @property GameVersion|null           $gameVersion
 * @property Dungeon|null               $dungeon
 * @property PatreonAdFreeGiveaway|null $patreonAdFreeGiveaway
 *
 * @property bool $is_admin
 *
 * @property EloquentCollection<int, DungeonRoute>  $dungeonRoutes
 * @property EloquentCollection<int, UserReport>    $reports
 * @property EloquentCollection<int, Team>          $teams
 * @property EloquentCollection<int, Role>          $roles
 * @property EloquentCollection<int, Tag>           $tags
 * @property EloquentCollection<int, UserIpAddress> $ipAddresses
 *
 * @mixin Eloquent
 */
class User extends Authenticatable implements LaratrustUser
{
    use GeneratesPublicKey;
    use HasIconFile;
    use HasRolesAndPermissions;
    use Notifiable;
    use HasTags;
    use HasFactory;

    public const string MAP_FACADE_STYLE_SPLIT_FLOORS = 'split_floors';
    public const string MAP_FACADE_STYLE_FACADE       = 'facade';

    public const array MAP_FACADE_STYLE_ALL = [
        self::MAP_FACADE_STYLE_SPLIT_FLOORS,
        self::MAP_FACADE_STYLE_FACADE,
    ];

    public const string DEFAULT_MAP_FACADE_STYLE = self::MAP_FACADE_STYLE_FACADE;

    public const int DEFAULT_KILL_ZONE_PATH_WEIGHT = 5;

    public const string THEME_DARKLY   = 'darkly';
    public const string THEME_LUX      = 'lux';
    public const string THEME_XALATATH = 'vapor';
    public const string DEFAULT_THEME  = self::THEME_DARKLY;

    public const array THEME_ALL = [
        self::THEME_DARKLY,
        self::THEME_LUX,
        self::THEME_XALATATH,
    ];

    /**
     * Can be used in certain circumstances to override the map facade style for the current request
     */
    private static ?string $OVERRIDE_MAP_FACADE_STYLE = null;

    /**
     * @var string Have to specify connection explicitly so that Tracker still works (has its own DB)
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'game_server_region_id',
        'patreon_user_link_id',
        'game_version_id',
        'dungeon_id',
        'public_key',
        'oauth_id',
        'name',
        'email',
        'echo_color',
        'map_facade_style',
        'kill_zone_path_weight',
        'password',
        'legal_agreed',
        'legal_agreed_ms',
    ];

    /**
     * The attributes that should be visible for outsiders.
     *
     * @var list<string>
     */
    protected $visible = [
        'id',
        'public_key',
        'name',
        'echo_color',
    ];

    protected $appends = [
        'initials',
    ];

    protected $with = [
        'iconfile',
        'patreonUserLink',
        'dungeon',
        'gameVersion',
        'roles',
    ];

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(Role::ROLE_ADMIN);
    }

    /** @return HasMany<DungeonRoute, $this> */
    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class, 'author_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(UserReport::class);
    }

    public function patreonUserLink(): HasOne
    {
        return $this->hasOne(PatreonUserLink::class);
    }

    public function gameServerRegion(): BelongsTo
    {
        return $this->belongsTo(GameServerRegion::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_users');
    }

    public function patreonAdFreeGiveaway(): HasOne
    {
        return $this->hasOne(PatreonAdFreeGiveaway::class, 'receiver_user_id');
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(UserIpAddress::class);
    }

    /**
     * Checks if this user has registered using OAuth or not.
     */
    public function isOAuth(): bool
    {
        return empty($this->password);
    }

    /**
     * Checks if this user has paid for a certain tier one way or the other.
     */
    public function hasPatreonBenefit(string $key): bool
    {
        // True for all admins
        $result = $this->hasRole(Role::ROLE_ADMIN);

        // If we weren't an admin, check patreon data
        if (!$result && $this->patreonUserLink !== null && isset(PatreonBenefit::ALL[$key])) {
            $result = $this->patreonUserLink->patreonbenefits()->where('patreon_benefits.id', PatreonBenefit::ALL[$key])->exists();
        }

        return $result;
    }

    /**
     * Get a list of tiers that this User has access to.
     */
    public function getPatreonBenefits(): Collection
    {
        // Admins have all patreon benefits
        if ($this->hasRole(Role::ROLE_ADMIN)) {
            $result = collect(array_keys(PatreonBenefit::ALL));
        } elseif (isset($this->patreonUserLink)) {
            $result = $this->patreonUserLink->patreonBenefits->pluck(['key']);
        } else {
            $result = collect();
        }

        return $result;
    }

    public function hasAdFreeGiveaway(): bool
    {
        return PatreonAdFreeGiveaway::where('receiver_user_id', $this->id)->exists();
    }

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
        return $this->dungeonRoutes()->count() < config('keystoneguru.registered_user_dungeonroute_limit') ||
            $this->hasPatreonBenefit(PatreonBenefit::UNLIMITED_DUNGEONROUTES);
    }

    /**
     * Get the amount of routes a user may still create.
     *
     * NOTE: Will be inaccurate if the user is a Patron. Just don't call this function then.
     */
    public function getRemainingRouteCount(): int
    {
        return (int)max(
            0,
            config('keystoneguru.registered_user_dungeonroute_limit') - $this->dungeonRoutes()->count(),
        );
    }

    /**
     * Sends the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
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
            } catch (Exception) {
                $newOwner = null;
            }

            /** @var Team $team */
            $teams['teams'][$team->name] = [
                'result'    => $team->members()->count() === 1 ? 'deleted' : 'new_owner',
                'new_owner' => $newOwner,
            ];
        }

        return array_merge($teams, [
            'patreon' => [
                'unlinked' => $this->patreonUserLink !== null,
            ],
            'dungeonroutes' => [
                'delete_count' => ($this->dungeonRoutes()->count() - $this->dungeonRoutes()->isSandbox()->count()),
            ],
            'reports' => [
                'delete_count' => ($this->reports()->where('status', 0)->count()),
            ],
        ]);
    }

    public static function getCurrentUserMapFacadeStyle(): string
    {
        return self::$OVERRIDE_MAP_FACADE_STYLE ??
            Auth::user()?->map_facade_style ?? // @phpstan-ignore nullsafe.neverNull
            $_COOKIE['map_facade_style'] ??
            User::DEFAULT_MAP_FACADE_STYLE;
    }

    public static function getCurrentUserKillzonePathWeight(): int
    {
        return Auth::user()?->kill_zone_path_weight ?? (int)($_COOKIE['kill_zone_path_weight'] ?? self::DEFAULT_KILL_ZONE_PATH_WEIGHT); // @phpstan-ignore nullsafe.neverNull
    }

    public static function forceMapFacadeStyle(?string $mapFacadeStyle): void
    {
        self::$OVERRIDE_MAP_FACADE_STYLE = $mapFacadeStyle;
    }

    public static function isThemeDark(string $theme): bool
    {
        return in_array($theme, [self::THEME_DARKLY, self::THEME_XALATATH]);
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete user properly if it gets deleted
        static::deleting(static function (User $user) {
            $user->dungeonRoutes()->delete();
            $user->reports()->delete();
            $user->patreonUserLink()->delete();
            foreach ($user->teams as $team) {
                // Remove ourselves from the team
                $team->removeMember($user);

                /** @var Team $team */
                if (!$team->isUserAdmin($user)) {
                    continue;
                }

                /** @var Team $team */
                try {
                    $newAdmin = $team->getNewAdminUponAdminAccountDeletion($user);
                    if ($newAdmin !== null) {
                        // Appoint someone else admin
                        $team->changeRole(User::find($newAdmin->id), TeamUser::ROLE_ADMIN);
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
