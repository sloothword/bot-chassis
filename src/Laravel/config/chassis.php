<?php
use Chassis\Controller\DummyController;
use Chassis\Controller\Bubbling;

return [


    // Use your own bot and storage implementation
//    'classes' => [
//        'bot' => Chassis\Bot\ControllerBot::class,
//        'storage' => Chassis\Integration\Redis\Storage::class
//    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bots
    |--------------------------------------------------------------------------
    |
    | Here are each of the telegram bots config.
    */
    'bots' => [
        'mybot' => [
            'username'  => 'BOT-USERNAME',
            'token' => env('TELEGRAM_BOT_TOKEN', 'BOT-TOKEN'),
            'controllers' => [
                ['text', EchoController::class, 'once', Bubbling::NONE],
                ['/double', EchoController::class, 'twice', Bubbling::AFTER],
                ['/delayed', EchoController::class, 'delayed', Bubbling::AFTER]
            ],
        ],
    ],

    'telegram' => [
        // Example Proxy config e.g. for Fiddler
//        'http_client_handler' =>
//            new GuzzleHttpClient(
//                new Client(
//                    ['proxy' => "localhost:8888", 'verify' => false]
//                )
//            )
    ],
];