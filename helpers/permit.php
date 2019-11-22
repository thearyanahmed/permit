<?php

use Prophecy\Permit\Permit;

if(!function_exists('user_can')) {
    function user_can($permission,$module,bool $findInSession = true) {
        $permit = app()->make(Permit::class);
        return $permit->can($module,$permission,$findInSession);
    }
}
