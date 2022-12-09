<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionUserLogsRequest;
use App\Models\SubscriptionUserLogs;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionUserLogsController extends Controller
{
    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function index(): JsonResponse
    {
        $sul = SubscriptionUserLogs::latest()->get();

        return $this->response(ResponseCode::HTTP_OK, ['data'=>$sul]);
    }

    /**
     * store resources
     *
     * @param  SubscriptionUserLogsRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionUserLogsRequest $request)
    {
        try {
            $sul = SubscriptionUserLogs::create($request->all());
        } catch (\Exception $err) {
            return $this->response(ResponseCode::HTTP_UNPROCESSABLE_ENTITY,['data'=> 'The subscription is not created'], $err);
        }

        return $this->response(ResponseCode::HTTP_CREATED, ['data'=>$sul]);
    }

    /**
     * get specific subscription user log by $id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id)
    {
        try{
            $sul = SubscriptionUserLogs::findOrFail($id);
        } catch (\Exception $err){
            return $this->response(ResponseCode::HTTP_UNPROCESSABLE_ENTITY, ['data'=>'The subscription does not exist'], $err);
        }

        return $this->response(ResponseCode::HTTP_OK,['data' => $sul]);
    }

    /**
     * update specific subscription user log by id
     *
     * @param SubscriptionUserLogsRequest $request
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionUserLogsRequest $request, int $id)
    {
        try {
            $sul = SubscriptionUserLogs::findOrFail($id);
            $sul->update($request->all());
        } catch (\Exception $err){
            return $this->response(ResponseCode::HTTP_UNPROCESSABLE_ENTITY, ['data'=>'The subscription does not updated'], $err);
        }

        return $this->response(ResponseCode::HTTP_OK, ['data' => $sul]);
    }

    /**
     * remove specific subscription user log by id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $id)
    {
        try{
            SubscriptionUserLogs::destroy($id);
        } catch (\Exception $err) {
            return $this->response(ResponseCode::HTTP_UNPROCESSABLE_ENTITY,['data'=>'The subscription does not deleted'], $err);
        }

        return $this->response(ResponseCode::HTTP_OK, ['data' => 'The subscription is deleted.']);
    }
}
