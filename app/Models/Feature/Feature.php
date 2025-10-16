<?php

namespace App\Models\Feature;

use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
            ->where('scope', sprintf('%s|%d', User::class, 1))
            ->first();

        return $feature?->value === 'true';
    }
}
