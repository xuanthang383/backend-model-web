<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Chuyển request thành CustomRequest để Laravel hiểu
        $request = \App\Http\Requests\Request::createFrom($request);

        return $next($request);
    }
}
