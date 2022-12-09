<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionJobsRequest;
use App\Models\SubscriptionJobs;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionJobsController extends Controller
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
        $sj = SubscriptionJobs::latest()->get();

        return response( ['data'=>$sj],ResponseCode::HTTP_OK,);
    }

    /**
     * store resources
     *
     * @param  SubscriptionJobsRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionJobsRequest $request)
    {
        try {
            $sj = SubscriptionJobs::create($request->all());
        } catch (\Exception $err) {
            return response(['data'=> 'The subscription is not created'],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response(['data'=>$sj],ResponseCode::HTTP_CREATED );
    }

    /**
     * get specific subscription job by $id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id)
    {
        try{
            $sj = SubscriptionJobs::findOrFail($id);
        } catch (\Exception $err){
            return response(['data'=>'The subscription does not exist'],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->response(ResponseCode::HTTP_OK,['data' => $sj]);
    }

    /**
     * update specific subscription job by id
     *
     * @param SubscriptionJobsRequest $request
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionJobsRequest $request, int $id)
    {
        try {
            $sj = SubscriptionJobs::findOrFail($id);
            $sj->update($request->all());
        } catch (\Exception $err){
            return response(['data'=>'The subscription does not updated'],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response(['data' => $sj], ResponseCode::HTTP_OK );
    }

    /**
     * remove specific subscription job by id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $id)
    {
        try{
            SubscriptionJobs::destroy($id);
        } catch (\Exception $err) {
            return response(['data'=>'The subscription does not deleted'], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response(['data' => 'The subscription is deleted.'],ResponseCode::HTTP_OK );
    }
}
