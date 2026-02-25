<?php

return [

    'use_broadcaster' => env('REVERB_APP_ID', 'elevadores'),

    'servers' => [
        'reverb' => [
            'host'    => env('REVERB_HOST', '127.0.0.1'),
            'port'    => env('REVERB_PORT', 8080),
            'scheme'  => env('REVERB_SCHEME', 'http'),
            'options' => [
                'tls' => [],
            ],
        ],
    ],

    'apps' => [
        'provider' => 'config',
        'ids'      => [
            [
                'id'              => env('REVERB_APP_ID', 'elevadores'),
                'key'             => env('REVERB_APP_KEY', 'local-key'),
                'secret'          => env('REVERB_APP_SECRET', 'local-secret'),
                'name'            => 'Elevadores SaaS',
                'options'         => [
                    'host'            => env('REVERB_HOST', '127.0.0.1'),
                    'port'            => env('REVERB_PORT', 8080),
                    'scheme'          => env('REVERB_SCHEME', 'http'),
                    'use_tls'         => false,
                ],
                'allowed_origins' => ['*'],
                'ping_interval'   => env('REVERB_APP_PING_INTERVAL', 60),
                'activity_timeout'=> env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'capacity'        => null,
                'allowed_origins' => ['*'],
                'allow_client_messages' => false,
                'enable_statistics'     => true,
            ],
        ],
    ],

];
