<?php


namespace App\Helpers;


use Amqp;
use App\Constants\Tables;
use App\Models\SubscriptionPlanEntities;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Helper
{
    public static function datetime_diff_in_minute($datetime1, $datetime2)
    {
        $interval = date_diff($datetime1, $datetime2);
        if ($interval == false) {
            return -1;
        }

        $minutes = 0;
        if ($interval->d > 0) {
            $minutes += $interval->d*24*60;
        }
        if ($interval->h > 0) {
            $minutes += $interval->h*60;
        }
        $minutes+= $interval->i;
        return $minutes;
    }

    public static function absolute_datetime_diff_in_minute($datetime1, $datetime2)
    {
        $interval = date_diff($datetime1, $datetime2, true);
        if ($interval == false) {
            return -1;
        }

        $minutes = 0;
        if ($interval->days > 0) {
            $minutes += $interval->days*24*60;
        }
        if ($interval->h > 0) {
            $minutes += $interval->h*60;
        }
        $minutes+= $interval->i;
        return $minutes;
    }

    /**
     * returns 1 if $time is less than now, otherwise returns 0;
     * @param $time
     * @throws \Exception
     */
    public static function compare_time_with_now($time) {
        try {
            $st = new \DateTime($time);
            $now = new \DateTime(Carbon::now()->toDateTimeLocalString());
            if ($st < $now) {
                return 1;
            }
            return 0;
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public static function calculate_valid_start_end_datetime($current, $now, $start, $end) {
        // print_r(sprintf("\n%s\n",$end->format('Y-m-d H:i:s')));
        try {
            $start = new \DateTime($start, new \DateTimeZone('Asia/Tehran'));
            if ($end == null) {
                $end = new \DateTime($now, new \DateTimeZone('Asia/Tehran'));
                // $end = (new \DateTime());
                // $end->setTimezone(new \DateTimeZone('Asia/Tehran'));
            } else {
                $end = new \DateTime($end, new \DateTimeZone('Asia/Tehran'));
            }
            // print_r(sprintf("\n%s\n",$end->format('Y-m-d H:i:s')));

            $result = [ 'start' => $start, 'end' => $end];

            $totalEnd = new \DateTime($current->settelment_date, new \DateTimeZone('Asia/Tehran'));

            $totalStart = clone($totalEnd);
            $totalStart->sub( new \DateInterval( sprintf("P%dD", $current->settlement_duration) ) );
//            dd($start->format('Y-m-d H:i:s'), $totalStart->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $totalEnd->format('Y-m-d H:i:s'));
            if ($start < $totalStart) {
                $result['start'] =  $totalStart;
            }

            if ($end > $totalEnd) {
                $result['end'] =  $totalEnd;
            }
        } catch (\Exception $e) {
            // TODO LOG
            return $result;
        }
        return $result;
    }

    public static function username_masker(string $username) {
//        $username = "amirhossein.golabadi1996@gmail.com";
        $result = "";
        $isEmail = false;
        $postEmailPart = "";
        if ( self::checkEmail($username) ) {
            $isEmail = true;
            $loc = strpos($username, '@');
            $postEmailPart =  substr($username, $loc);
            $username = substr($username, 0, $loc);
        }
        $userNameLen = strlen($username);
        $maskedCharNum = strlen($username)/3;

        if ($userNameLen == 0) {
            return $result;
        } else {
            $remaining = $userNameLen%3;
            $maskedStr = "";
            for ($i = 0; $i<(int)$maskedCharNum; $i++) {
                $maskedStr = $maskedStr."*";
            }

            if ($remaining != 0) {
                $maskedStr = $maskedStr."*";
            }
            $result = substr($username, 0, $maskedCharNum) . $maskedStr . substr($username, $maskedCharNum + strlen($maskedStr), $maskedCharNum+1);
        }

        if ($isEmail) {
            $result = $result.$postEmailPart;
        }
        return $result;
    }

    public static function checkEmail($email) {
        return (preg_match("/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/", $email) || !preg_match("/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/", $email)) ? false : true;
    }

    public static function parse_specific_resource_option(string $prefix, $request = null) {
        return $prefix;
    }

    /**
     * send data to rabbitMQ
     * CHANNEL: indexer_elastic
     * @param array $sendToQueue
     * @return bool
     */
    public static function send_to_elastic(array $sendToQueue) {

        try {
            $message = json_encode(['index' => 'fidibo-content-v1.0','cols' => $sendToQueue]);
            Amqp::publish('/', $message, [
                'queue' => env('AMQP_QUEUE_SUBSCRIPTION'),
//                'exchange_type' => env("AMQP_EXCHANGE_TYPE"),
//                'exchange' => env("AMQP_EXCHANGE"),
            ]);

        } catch (\Exception $err) {
            Log::channel('gelf')->error("[Helper][Send to Elastic] error in sending data to rabbitMQ indexer channel.");
            return false;
        }
        Log::channel('gelf')->info("[Helper][Send to Elastic] data successfully sent into indexer elastic channel in rabbitMQ.");
        return true;
    }


    /**
     * Divide a single number into a set of unique random numbers in PHP
     *
     * Input: generateRandomNumbers(100, 10)
     *
     * Output: Array (
        [0] => 0
        [1] => 1
        [2] => 6
        [3] => 11
        [4] => 14
        [5] => 13
        [6] => 3
        [7] => 6
        [8] => 13
        [9] => 33
        )
     */
    public static function distributeRandomNumbersOverSpecificAmount($max, $count)
    {
        $numbers = [];
        for ($i = 1; $i < $count; $i++) {
            $random = mt_rand(0, $max / ($count - $i));
            $numbers[] = $random;
            $max -= $random;
        }

        $numbers[] = $max;

        shuffle($numbers);

        return $numbers;
    }

    public static function getBooksFromPapi ($entities)
    {
        for ($i = 0; $i < 5; $i++) {
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/book/by/id',[
                'json' => [ 'book_ids' => $entities, 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody()->getContents(),true);
            } else if ($response->getStatusCode() == 404) {
                return [];
            } else {
                continue;
            }
        }
        return [];
    }
}
