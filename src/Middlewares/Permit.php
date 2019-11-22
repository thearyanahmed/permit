<?php

namespace Prophecy\Permit\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;

class Permit
{
    protected $redirectTo = '/';
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $module, $permission)
    {
        if (user_can($permission,$module) === true) {
            return $next($request);
        }
        return redirect($this->redirectTo);
    }
}
