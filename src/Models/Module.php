<?php

namespace Prophecy\Permit\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';

    protected $guarded = ['id'];

    public function permissions()
    {
        return $this->hasManyThrough(Permission::class,Ability::class,'module_id','id','id','permission_id');
    }
}
