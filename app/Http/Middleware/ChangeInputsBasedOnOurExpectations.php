<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChangeInputsBasedOnOurExpectations
{
    /**
     * Handle an incoming request and change it based on controllers expectations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        if (isset($input['page'])) {
            $input['page'] = $input['page'] - 1;
            $request->replace($input);
//            \Log::info($request->all()); // Shows modified request
        }
        return $next($request);
    }
}
