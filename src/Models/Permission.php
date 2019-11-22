<?php

namespace Prophecy\Permit\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $guarded = ['id'];
}
