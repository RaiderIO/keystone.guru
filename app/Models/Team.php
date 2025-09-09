<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Traits\GeneratesPublicKey;
use App\Models\Traits\HasIconFile;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property int                      $id
 * @property string                   $public_key
 * @property string                   $name
 * @property string                   $description
 * @property string                   $invite_code
 * @property string                   $default_role
 *
 * @property Carbon                   $updated_at
 * @property Carbon                   $created_at
 *
 * @property Collection<TeamUser>     $teamUsers
 * @property Collection<User>         $members
 * @property Collection<DungeonRoute> $dungeonroutes
 *
 * @mixin Eloquent
 */
class Team extends Model
{
    use HasIconFile;
    use GeneratesPublicKey;

    protected $visible = [
        'name',
        'description',
        'public_key',
    ];

    protected $fillable = ['default_role'];

    protected $with = ['iconfile'];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'public_key';
    }

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_users');
    }

    public function dungeonroutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    /**
     * Get the amount of routes that are visible for everyone in the team
     */
    public function getVisibleRouteCount(): int
    {
        return $this->dungeonroutes()->whereIn('published_state_id', PublishedState::whereIn('name', [
            PublishedState::TEAM,
            PublishedState::WORLD,
            PublishedState::WORLD_WITH_LINK,
        ])->get()->pluck('id'))->count();
    }

    /**
     * Checks if a user can add/remove a route to this team or not.
     *
     * @param User $user
     */
    public function canAddRemoveRoute(User $user): bool
    {
        $userRole = $this->getUserRole($user);

        // Moderator or higher
        return $userRole !== null && TeamUser::ALL_ROLES[$userRole] >= TeamUser::ALL_ROLES[TeamUser::ROLE_MODERATOR];
    }

    /**
     * Adds a route to this Team.
     *
     * @param DungeonRoute $dungeonRoute The route to add.
     * @return bool True if successful, false if not (already assigned, for example).
     */
    public function addRoute(DungeonRoute $dungeonRoute): bool
    {
        $result = false;
        // Not set
        if ($dungeonRoute->team_id === null) {
            $dungeonRoute->team_id = $this->id;
            $dungeonRoute->save();
            $result = true;
        }

        return $result;
    }

    /**
     * Removes a route from this Team.
     *
     * @param DungeonRoute $dungeonRoute The route to remove.
     * @return bool True if successful, false if not (already removed, for example).
     */
    public function removeRoute(DungeonRoute $dungeonRoute): bool
    {
        $result = false;
        // Set already
        if ($dungeonRoute->team_id !== null) {
            // Delete all existing team tags from this route
            $dungeonRoute->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM])->delete();

            $dungeonRoute->team_id = null;
            $dungeonRoute->save();
            $result = true;
        }

        return $result;
    }

    /**
     * Get the role of a user in this team, or false if the user does not exist in this team.
     *
     * @param User $user
     * @return string|null
     */
    public function getUserRole(User $user): ?string
    {
        /** @var TeamUser $teamUser */
        $teamUser = $this->teamUsers()->where('user_id', $user->id)->first();

        return $teamUser?->role;
    }

    /**
     * Get the roles that a user may assign to other users in this team.
     *
     * @param User $user The user attempting to change roles.
     * @param User $targetUser The user that is targeted for a role change.
     */
    public function getAssignableRoles(User $user, User $targetUser): array
    {
        $userRole       = $this->getUserRole($user);
        $targetUserRole = $this->getUserRole($targetUser);
        $result         = [];

        // If both users have a valid role (should always be the case)
        if ($userRole !== null && $targetUserRole !== null) {
            $roles             = TeamUser::ALL_ROLES;
            $userRoleKey       = $roles[$userRole];
            $targetUserRoleKey = $roles[$targetUserRole];

            $admin     = $roles[TeamUser::ROLE_ADMIN];
            $moderator = $roles[TeamUser::ROLE_MODERATOR];
            // For now, admins cannot be demoted to anything else
            if ($targetUserRoleKey !== $admin) {
                // If the current user is a moderator or admin, and (if user is admin or the current user outranks the other user)
                if ($userRoleKey >= $moderator && ($userRoleKey === $admin || $userRoleKey > $targetUserRoleKey)) {

                    // Count down from all roles that exist, starting by the role the user currently has
                    for ($i = $userRoleKey; $i > 0; $i--) {
                        // array_search to find key by value
                        $result[] = array_search($i, $roles, true);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Checks if a specific user may change the role of another user into a specific other role.
     *
     * @TODO Should this go to a Policy?
     *
     * @param User   $user
     * @param User   $targetUser
     * @param string $role
     * @return bool
     */
    public function canChangeRole(User $user, User $targetUser, string $role): bool
    {
        $result = false;
        $roles  = TeamUser::ALL_ROLES;

        // Only if it's a valid role
        if (isset($roles[$role])) {
            $userRole       = $this->getUserRole($user);
            $targetUserRole = $this->getUserRole($targetUser);

            if ($userRole !== null && $targetUserRole !== null) {
                $userRoleKey       = $roles[$userRole];
                $targetUserRoleKey = $roles[$targetUserRole];
                $targetRoleKey     = $roles[$role];

                // User has a bigger role, and then only up to where the current user is (no promotions past their own
                // rank) the person, and only users who are currently a moderator or admin may change roles
                $result = $userRoleKey > $targetUserRoleKey && $userRoleKey >= $targetRoleKey && $userRoleKey >= $roles[TeamUser::ROLE_MODERATOR];
            }
        }

        return $result;
    }

    /**
     * Changes the role of a user in this team.
     *
     * @param User   $user The user of which the role should be changed.
     * @param string $role The new role of the user.
     */
    public function changeRole(User $user, string $role): void
    {
        /** @var TeamUser $teamUser */
        $teamUser = $this->teamUsers()->where('user_id', $user->id)->first();
        $roles    = TeamUser::ALL_ROLES;
        // Only when user is part of the team, and when the role is a valid one.
        if ($teamUser !== null && isset($roles[$role])) {
            // Update the role with the new one
            $teamUser->role = $role;
            $teamUser->save();
        }
    }

    /**
     * @return bool Checks if the current user is a member of this team.
     */
    public function isCurrentUserMember(): bool
    {
        return $this->isUserMember(Auth::user());
    }

    /**
     * Gets if a user is an admin and may perform admin actions.
     *
     * @param User $user
     */
    public function isUserAdmin(User $user): bool
    {
        $userRole = $this->getUserRole($user);

        return $userRole === TeamUser::ROLE_ADMIN;
    }

    /**
     * Checks if the user is a collaborator or higher.
     *
     * @param User $user
     * @return bool True if the user is, false if not.
     */
    public function isUserCollaborator(User $user): bool
    {
        $userRole = $this->getUserRole($user);

        return $userRole !== null && $this->getUserRole($user) !== TeamUser::ROLE_MEMBER;
    }

    /**
     * Gets if a user is a moderator and may perform moderation actions.
     *
     * @param User $user
     */
    public function isUserModerator(User $user): bool
    {
        $userRole = $this->getUserRole($user);

        return $userRole === TeamUser::ROLE_MODERATOR || $userRole === TeamUser::ROLE_ADMIN;
    }

    /**
     * Checks if a user is a member of this team or not.
     *
     * @param User|null $user
     * @return bool
     */
    public function isUserMember(?User $user): bool
    {
        return $user !== null && $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Checks if a user can remove another user from the team.
     *
     * @param User $targetUser The user that's to be removed.
     */
    public function canRemoveMember(User $user, User $targetUser): bool
    {
        $result = false;

        $userRole       = $this->getUserRole($user);
        $targetUserRole = $this->getUserRole($targetUser);

        if ($userRole !== null && $targetUserRole !== null) {
            $roles = TeamUser::ALL_ROLES;
            // Moderator or higher
            $userRoleKey       = $roles[$userRole];
            $targetUserRoleKey = $roles[$targetUserRole];

            // Be admin, or moderator that's removing normal users
            $result = $userRoleKey === $roles[TeamUser::ROLE_ADMIN] ||
                ($userRoleKey === $roles[TeamUser::ROLE_MODERATOR] && $userRoleKey > $targetUserRoleKey) ||
                $user->id === $targetUser->id;
        }

        return $result;
    }

    /**
     * Removes a member from this Team.
     *
     * @param User $member The user to remove.
     * @return bool True if successful, false if not (already removed, for example).
     */
    public function removeMember(User $member): bool
    {
        $result = false;
        // Only if the user could be found..
        if ($this->isUserMember($member)) {
            try {
                // If the user has a ad-free giveaway, see if we need to revoke it because they got removed from this team
                if ($member->patreonAdFreeGiveaway !== null && $this->members->pluck('id')->search($member->patreonAdFreeGiveaway->giver_user_id)) {
                    $member->patreonAdFreeGiveaway->delete();
                }

                $this->dungeonroutes()->where('team_id', $this->id)->where('author_id', $member->id)->update(['team_id' => null]);
                $result = TeamUser::where('team_id', $this->id)->where('user_id', $member->id)->delete();
            } catch (Exception) {
                logger()->error('Unable to remove member from team', [
                    'team' => $this,
                    'user' => $member,
                ]);
            }
        }

        return $result;
    }

    /**
     * Adds a member to this team.
     *
     * @param User   $user
     * @param string $role
     */
    public function addMember(User $user, string $role): void
    {
        // Prevent duplicate member listings
        if (!$this->isUserMember($user)) {
            TeamUser::create([
                'team_id' => $this->id,
                'user_id' => $user->id,
                'role'    => $role,
            ]);
        }
    }

    /**
     * Get the new owner of this team should a user decide to delete their account.
     *
     * @return User|null Null returned if there was no change in owner.
     *
     * @throws Exception
     *
     * @var User
     */
    public function getNewAdminUponAdminAccountDeletion(User $user): ?User
    {
        if ($this->getUserRole($user) !== TeamUser::ROLE_ADMIN) {
            throw new Exception(
                sprintf(
                    'User %d is not an admin itself - cannot fetch new admin for team %d!',
                    $user->id, $this->id,
                ));
        }

        $roles = TeamUser::ALL_ROLES;
        /** @var TeamUser|null $newOwner */
        $newOwner = $this->teamUsers->where('user_id', '!=', $user->id)
            ->sortByDesc(static fn($obj, $key) => $roles[$obj->role])
            ->first();

        return $newOwner?->user;
    }

    public function getAvailableTags(): Collection
    {
        return Tag::where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM])
            ->whereIn('model_id', $this->dungeonroutes->pluck('id'))
            ->get();
    }

    protected static function boot(): void
    {
        parent::boot();

        // Delete team properly if it gets deleted
        static::deleting(static function (Team $team) {
            // Delete icons
            $team->iconfile?->delete();
            // Remove any ad-free giveaways if the giver was part of this team
            foreach ($team->members->filter(function (User $member) {
                return $member->patreonAdFreeGiveaway !== null;
            }) as $teamMember) {
                /** @var User $teamMember */
                // If the giver of the patreon ad-free giveaway was part of this team
                if ($team->members->pluck('id')->search($teamMember->patreonAdFreeGiveaway->giver_user_id)) {
                    // The team connection no longer exists, and this user LOSES their ad-free giveaway connection
                    $teamMember->patreonAdFreeGiveaway->delete();
                }
            }
            // Delete all tags team tags belonging to our routes
            Tag::where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM])
                ->whereIn('model_id', $team->dungeonroutes->pluck('id')->toArray())
                ->delete();
            // Remove all users associated with this team
            TeamUser::where('team_id', $team->id)->delete();
            // Unassign all routes from this team
            DungeonRoute::where('team_id', $team->id)->update(['team_id' => null]);
        });
    }
}
