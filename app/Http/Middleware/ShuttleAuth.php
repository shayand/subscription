<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShuttleAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $reqAuthToken = $request->header('authorization', null);
            if ($reqAuthToken == null) {
                throw new \Exception("authorization required");
            }

            $tokenParts = explode(".", $reqAuthToken);
            $tokenType = explode(" ", $tokenParts[0]);
            if (count($tokenParts) != 3 && $tokenType != "Bearer") {
                throw new \Exception("authorization required");
            }
            $tokenPayload = base64_decode($tokenParts[1]);
            $jwtPayload = json_decode($tokenPayload);

            $request->attributes->set("operator_id", $jwtPayload->uid);
            $request->attributes->set("operator_role", $jwtPayload->role);
        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,Response::HTTP_UNAUTHORIZED );
        }

        return $next($request);
    }
}
