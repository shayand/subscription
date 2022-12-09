<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Define which configuration should be used
    |--------------------------------------------------------------------------
    */

    'use' => env('AMQP_ENV', 'indexer'),

    /*
    |--------------------------------------------------------------------------
    | AMQP properties separated by key
    |--------------------------------------------------------------------------
    */

    'properties' => [

        'indexer' => [
            'host' => env('AMQP_HOST', 'localhost'),
            'port' => env('AMQP_PORT', 5672),
            'username' => env('AMQP_USERNAME', 'username'),
            'password' => env('AMQP_PASSWORD', 'password'),
            'vhost' => env('AMQP_VHOST', '/'),
            'connect_options' => [],
            'ssl_options' => [],

            'exchange' => env('AMQP_EXCHANGE', 'amq.topic'),
            'exchange_type' => env('AMQP_EXCHANGE_TYPE', 'topic'),
            'exchange_passive' => false,
            'exchange_durable' => false,
            'exchange_auto_delete' => true,
            'exchange_internal' => false,
            'exchange_nowait' => false,
            'exchange_properties' => [],

            'queue_force_declare' => false,
            'queue_passive' => false,
            'queue_durable' => true,
            'queue_exclusive' => false,
            'queue_auto_delete' => false,
            'queue_nowait' => false,
            'queue_properties' => ['x-ha-policy' => ['S', 'all'], 'x-message-ttl' => ['I', '864000000'], 'x-queue-mode' => ['S', 'lazy']],

            'consumer_tag' => '',
            'consumer_no_local' => false,
            'consumer_no_ack' => false,
            'consumer_exclusive' => false,
            'consumer_nowait' => false,
            'timeout' => 0,
            'persistent' => false,

            'qos' => false,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_a_global' => false
        ],

        'campaign' => [
            'host' => env('AMQP_HOST', 'localhost'),
            'port' => env('AMQP_PORT', 5672),
            'username' => env('AMQP_USERNAME', 'username'),
            'password' => env('AMQP_PASSWORD', 'password'),
            'vhost' => env('AMQP_VHOST', '/'),
            'connect_options' => [],
            'ssl_options' => [],

            'exchange' => 'campaign.direct',
            'exchange_type' => 'direct',
            'exchange_passive' => false,
            'exchange_durable' => false,
            'exchange_auto_delete' => true,
            'exchange_internal' => false,
            'exchange_nowait' => false,
            'exchange_properties' => [],

            'queue_force_declare' => false,
            'queue_passive' => false,
            'queue_durable' => true,
            'queue_exclusive' => false,
            'queue_auto_delete' => false,
            'queue_nowait' => false,
            'queue_properties' => ['x-ha-policy' => ['S', 'all'], 'x-message-ttl' => ['I', '864000000'], 'x-queue-mode' => ['S', 'lazy']],

            'consumer_tag' => '',
            'consumer_no_local' => false,
            'consumer_no_ack' => false,
            'consumer_exclusive' => false,
            'consumer_nowait' => false,
            'timeout' => 0,
            'persistent' => false,

            'qos' => false,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_a_global' => false
        ]
    ],
];
