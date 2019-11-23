<?php

use Prophecy\Permit\Permit;

if(!function_exists('user_can')) {
    function user_can(string $permission,string $module,$roleId) {
        $permit = app()->make(Permit::class);
        return $permit->can($permission,$module,$findInSession);
    }
}

if(!function_exists('auth_user_can')) {
    function auth_user_can($permission,$module,bool $findInSession = true) {
        $permit = app()->make(Permit::class);
        return $permit->authUserCan($permission,$module,$findInSession);
    }
}
