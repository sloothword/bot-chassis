<?php
use Chassis\Controller\DummyController;
use Chassis\Controller\Bubbling;

return [
    
    /*
     * TODO: Move to DI / service container
     */
    'classes' => [
        'bot' => Chassis\Bot\Bot::class,
        'user' => Chassis\Integration\EloquentUser::class,
        'storage' => Chassis\Integration\EloquentStorage::class
    ],

    'sharedconfig' => [],

    'pipeline' => [],
    
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
            'shared' => [],
            'controllers' => [
                ['text', EchoController::class, 'once', Bubbling::NONE],
                ['\double', EchoController::class, 'twice', Bubbling::AFTER]
            ],          
        ],
    ]
];