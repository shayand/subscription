<?php


namespace App\Traits;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

trait MSLog
{
    /**
     * Post data over http client
     *
     * @param string $type
     * @param string $method
     * @param array $context
     * @param string $customMessage
     */
    public function log(string $type, string $method, array $context = [], string $customMessage = ''): void
    {
        try {
            $class = get_class($this);
            $context = array_merge($context, [
                'ip' => request()->getClientIp(),
                'action_name' => request()->route() ? request()->route()->getName() : 'no action defined',
                'method' => request()->getMethod(),
                'port' => request()->getPort(),
                'url' => URL::full()
            ]);

            if ($this->hasAdditionalField($context, 'id')) {
                $context['record_id'] = $context['id'];
                unset($context['id']);
            }

            Log::channel('gelf')
                ->info(sprintf("[%s][%s][%s] %s", $class, $method, $type, $customMessage), $context ?? []);
        } catch (\Exception $e) {
            report(new \Exception(sprintf("GRAYLOG EXCEPTION: %s", $e->getMessage()), 0, $e));
        }
    }

    /**
     * check if a field is present
     *
     * @param array $data
     * @param string $field
     * @return bool
     */
    public function hasAdditionalField(array $data, string $field): bool
    {
        return isset($data[$field]);
    }
}
