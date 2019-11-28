<?php

use Prophecy\Permit\Permit;

if(!function_exists('user_can')) {
    function user_can($permission,$module,$roleId) {
        $permit = app()->make(Permit::class);
        return $permit->can($permission,$module,$roleId);
    }
}



if(!function_exists('auth_user_can')) {
     function auth_user_can($permission,$module) {
        $findInSession = config('permit.find_in_session');

        $roleColumn = config('permit.role_column');

        $roleId = auth()->user()->{$roleColumn};

        $permit = app()->make(Permit::class);
        return $permit->authUserCan($permission,$module,$roleId,$findInSession);
    }
}


if(!function_exists('abilities_of')) {
    function abilities_of($role,$column = 'name') {
        $permit = app()->make(Permit::class);
        return $permit->findAbilitiesOf($role,$column);
    }
}
