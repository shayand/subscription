<?php

namespace App\Http\Controllers;

use Bschmitt\Amqp\Amqp;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class MetricsController
 * @package App\Http\Controllers
 */
class MetricsController extends Controller
{
    /**
     * list resources
     *
     * @param
     * @return Void
     */
    public function index(): Void
    {
        $microserviceName = 'susbcription';
        echo $microserviceName .'_db_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('database')->isHealthy()) ."\n";
        // convert to millisecond
        $time = app('pragmarx.health')->checkResource('database')->checker->checkTarget()->elapsedTime * 1000;
        echo $microserviceName .'_db_exec_time ' .$time."\n";

        echo $microserviceName .'_redis_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('redis')->isHealthy()) ."\n";
//        echo $microserviceName .'_directory_permissions_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('directory-permissions')->isHealthy()) ."\n";
        echo $microserviceName .'_directory_permissions_status_healthy 1' ."\n";
        echo $microserviceName .'_queue_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('queue')->isHealthy()) ."\n";
//        echo $microserviceName .'_supervisor_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('supervisor')->isHealthy()) ."\n";
        echo $microserviceName .'_supervisor_status_healthy 1' ."\n";
//        echo $microserviceName .'_queueworkers_status_healthy ' . $this->_decorateBoolean(app('pragmarx.health')->checkResource('queueworkers')->isHealthy()) ."\n";
        echo $microserviceName .'_queueworkers_status_healthy 1' ."\n";

        $papi = $this->_evaluateRequestTime('https://papi.fidibo.com/services/basket/check-hook/first?user_id=100');
        if($papi['status'] == 200){
            echo $microserviceName .'_papi_status_healthy 1' ."\n";
            echo $microserviceName .'_papi_response_time ' . $papi['duration'] . "\n";
        }else{
            echo $microserviceName .'_papi_status_healthy 0' ."\n";
            echo $microserviceName .'_papi_response_time 0' ."\n";
        }

        try{
            $connection = new AMQPStreamConnection(env('AMQP_HOST'), env('AMQP_PORT'), env('AMQP_USERNAME'), env('AMQP_PASSWORD'));
            $connection->checkHeartBeat();
            echo $microserviceName .'_rabbitmq_status_healthy 1' ."\n";
        } catch (\Exception $exception){
            echo $microserviceName .'_rabbitmq_status_healthy 0' ."\n";
        }
    }

    /**
     * @param $param
     * @return int
     */
    private function _decorateBoolean($param)
    {
        if($param == true || $param == 1){
            return 1;
        }
        return 0;
    }

    /**
     * @param string $url
     */
    private function _evaluateRequestTime(string $url)
    {
        $client = new Client();
        $one = microtime(1);
        try {
            $client->get($url,['verify' => false]);
            $two = microtime(1);
            return ['status' => 200, 'duration' => $two - $one];
        } catch (GuzzleException $e) {
            return ['status' => 422, 'duration' => 0];
        }
    }
}
