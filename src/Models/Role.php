<?php

namespace Prophecy\Permit\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $guarded = ['id'];

    protected $primaryKey = 'id';

    public function modules()
    {
        return $this->hasManyThrough(Module::class,Ability::class,'role_id','id','id','module_id');
    }
}
