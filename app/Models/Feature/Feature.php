<?php

namespace App\Models\Feature;

use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature as PennantFeature;

/**
 * @property int    $id
 * @property string $name
 * @property string $scope
 * @property string $value
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class Feature extends Model
{
    /**
     * The user whose stored values double as the global on/off switch of each feature - see getAdminValue().
     */
    public const int ADMIN_USER_ID = 1;

    protected function casts(): array
    {
        return [
            'id'         => 'int',
            'name'       => 'string',
            'scope'      => 'string',
            'value'      => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function getAdminValue(string $name): bool
    {
        /** @var Feature|null $feature */
        $feature = Feature::where('name', $name)
            ->where('scope', sprintf('%s|%d', User::class, self::ADMIN_USER_ID))
            ->first();

        return $feature?->value === 'true';
    }

    /**
     * Drop every stored feature value of the given user, so that they are resolved anew the next time the user
     * requests them. Necessary whenever the user's roles change - every feature in App\Features resolves off the
     * user's roles, but Pennant stores the value it resolved once and keeps serving it forever after.
     */
    public static function forgetAllForUser(User $user): void
    {
        // The admin user's stored values are the global on/off switch of each feature (see getAdminValue), so
        // dropping them would disable every feature site-wide until an admin toggles them all back on by hand
        if ($user->id === self::ADMIN_USER_ID) {
            return;
        }

        PennantFeature::for($user)->forget(PennantFeature::stored());
    }
}
