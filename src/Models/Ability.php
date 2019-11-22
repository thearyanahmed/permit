<?php

namespace Prophecy\Permit\Models;

use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    protected $table = 'role_permissions';

    protected $guarded = ['id'];

    protected $primaryKey = 'id';

    public function permissions()
    {
        return $this->hasMany(Permission::class,'id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class,'module_id');
    }
}
