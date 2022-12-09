<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Health\Checkers\Database;
use PragmaRX\Health\Checkers\DirectoryAndFilePresence;
use PragmaRX\Health\Support\Result;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

/**
 * Class UtilitiesController
 * @package App\Http\Controllers
 */
class UtilitiesController extends Controller
{
    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     */
    public function clearRedisCache(): JsonResponse
    {
        try {
            Cache::flush();
            return new JsonResponse(['status' => 'success','message' => 'Redis cache has been flushed']);
        } catch (\Exception $exception){
            return new JsonResponse(['status' => 'failed' ,'message' => 'unable to flush the cache'],ResponseCode::HTTP_REQUEST_TIMEOUT);
        }
    }
}
