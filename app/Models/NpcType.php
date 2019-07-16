<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 *
 * @mixin \Eloquent
 */
class NpcType extends Model
{
    protected $attributes = ['type_key'];
    protected $fillable = ['id', 'type'];
    public $timestamps = false;

    public function getTypeKeyAttribute()
    {
        return strtolower($this->type);
    }
}
