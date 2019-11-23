<?php

namespace Prophecy\Permit\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;

class Permit
{
    protected $redirectTo;

    public function __construct()
    {
        $this->redirectTo = config('permit.redirect_to');
    }

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
        if (auth_user_can($permission,$module) === true) {
            return $next($request);
        }
        return redirect()->route($this->redirectTo);
    }
}
