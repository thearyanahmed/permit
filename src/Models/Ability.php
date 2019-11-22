<?php

namespace Prophecy\Permit\Models;

use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    protected $table = 'role_permissions';

    protected $guarded = ['id'];

    protected $primaryKey = 'id';

    public function role()
    {
    	return $this->belongsTo(Role::class,'role_id');
    }

    public function module()
    {
    	return $this->belongsTo(Module::class,'module_id');
    }

    public function permission()
    {
    	return $this->belongsTo(Permission::class,'permission_id');
    }
}
