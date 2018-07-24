<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 24/07/2018
 * Time: 22:33
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IconFileModel extends Model
{

    /**
     * @var array Always eager load the iconfile
     */
    protected $with = ['iconfile'];

    function iconfile()
    {
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }
}