# Installation
Install PHP7, Laravel and Redis. Configure Laravel to use your database and redis instance.

Add `bot-chassis` to the composer.json:
```
composer require sloothword/bot-chassis
```

# Configuration

## Example chassis.php
```php
use App\Chassis\Controller\{CatchallController, TextController, CallbackController};
use Chassis\Controller\Bubbling;

return [
    
    'bots' => [
        'mybot' => [
            'username'  => 'TELEGRAM_BOT_NAME',
            'token' => env('TELEGRAM_BOT_TOKEN', '1234'),
            'controllers' => [
            	['*', CatchallController::class],
            	['text', TextController::class, 'handleText', Bubbling::BEFORE],
                ['/text', TextController::class, 'handleCommand'],
                ['callback_query', CallbackController::class]
            ],            
        ],
    ],
    
    'classes' => [
    	'bot' => Chassis\Bot\ControllerBot::class,
        'storage' => Chassis\Integration\RedisStorage::class
    ],

	// Configuration as done in telegram.php
    'telegram' => [
        // Example Proxy config e.g. for Fiddler
        //'http_client_handler' =>
        //    new GuzzleHttpClient(
        //        new Client(
        //            ['proxy' => "localhost:8888", 'verify' => false]
        //        )
        //    )
    ],
];
```

## Bot Config
```php
'bots' => [
	'firstbot' => [
    	'username' => 'TELEGRAM_BOT_NAME',
        'token' => 'TELEGRAM_BOT_TOKEN'
        'controllers' => [...],
    ],
    'secondbot' => [...],
    'thirdbot' => [...]
];
```
- Each entry in the bots array configures one bot.
- The first entry is always the default bot
- `username` and `token` contain the telegram credentials
- in `controllers` you can define the routing between incoming updates and handling controller


## Controllers

You can use the `controllers` array to configure, when to call which Controller methods.

Each entry defines one route: 
`["/somecommand", MyController::class, "myMethod", Bubbling::BEFORE]`

\# | Entry | Example | Defines | Mandatory
--| ----- | ------- | ----------- | --------
0 | Trigger | `/somecommand` | when to execute | Yes
1 | Controller | `MyController::class` | which Controller to call | Yes
2 | Method | `myMethod` | which Controller method to call | No, default is `handle`
3 | Bubbling | `Bubbling::BEFORE` | in which order | No, default is `Bubbling::NONE`


As trigger you can either use the short key or the full hierarchy from the following table:

Hierarchy | Key | Called
--- | ------
update | * | for every update
update.message | message | for every message
update.message.text | text | for every message with text
update.message.text.command | command | for every command (text message starting with `/`)
update.message.text.command.XXX | /XXX | for command `XXX` (text message starting with `/XXX`)
update.message.audio | audio | for every message with audio
update.message.XXX | XXX | same for 'document', 'photo', 'sticker', 'video', 'voice', 'contact', 'location', 'venue', 'new_chat_member', 'left_chat_member', 'new_chat_title', 'new_chat_photo', 'delete_chat_photo', 'group_chat_created', 'supergroup_chat_created',  'channel_chat_created', 'migrate_to_chat_id', 'migrate_from_chat_id', 'pinned_message'
update.inline_query | inline_query | for every inline query
update.chosen_inline_result | chosen_inline_result | for chosen_inline_result callbacks
update.callback_query | callback_query | for callback_queries

The order in which multiple matching triggers are called is defined by the Bubbling parameter:

Key | Description
--- | ------- 
`Bubbling::BEFORE` | Handler gets called before any more specific handler
`Bubbling::AFTER` | Handler gets called after any more specific handler
`Bubbling::NONE` (default) | Handler gets only called if there is no more specific handler available


##### Example:
```
'handler' => [
    ['*', ExampleController::class, 'handleAll', Bubbling::BEFORE ],
    ['message', ExampleController::class, 'handleMessage', Bubbling::AFTER ],
    ['text', ExampleController::class],	// <-- default 'handle' and Bubbling::NONE
    ['/XXX', ExampleController::class, 'handleXXX', Bubbling::NONE ]
]
```


Incoming Message | Called Method (of `ExampleController`)
---------------- | --------------
Any update not containing a message | `handleAll()`
A message without text | `handleAll()` then `handleMessage()`
A text message | `handleAll()` then `handle()` then `handleMessage()`
`/XXX` | `handleAll()` then `handleXXX()` then `handleMessage()`


## Classes
You can define your own Bot and Storage classes with the `classes` config array.

Key | Requirements
--- | ------------
`bot`| `extends Bot`
`storage`| `implements StorageInterface`

> Note: I actually plan to remove this in favor of a full dependency injection system soon

# Integration

## Plain PHP



## Laravel
Add the LaravelServiceProvider in the app.php
```
'providers' => [
        ...
        
        Chassis\Laravel\ChassisServiceProvider::class,
```
Publish the configuration (and migrations)
```
artisan vendor:publish
```

The `Telegram` facade returns a BotsManager as configured by `config/chassis.php`.

#### Artisan

The following artisan commands are available for your convenience:

Command | Description
-------------- | -----------
`chassis:handle`| Load and process all pending telegram updates. Calls default bot if not specified by `--bot=BOTNAME`. Add `--loop` to continously check for new updates. Command uses long polling with `--timeout=10`. 
`chassis:flush` | Flushes all MetaData from Redis and all pending telegram updates (without processing)


## Webhook
```
// Plain
$botsManager = new BotsManager($config);
$bot = $botsManager->bot(); // or ->bot($botName)
$bot->checkForUpdates(true);

// Laravel
Telegram::bot()->checkForUpdates(true);
```

## <a name="differences"></a> Coming from telegram-bot-sdk

Instead of configuring the telegram-bot-sdk through the `telegram.php`, you could also at the configuration keys to the `telegram` array in `chassis.php`.

Some notes:
- in general configuration done directly in `chassis.php` overrides the `telegram` array in `chassis.php`, which again overrides configuration done in `telegram.php`.
- Bots (array `bots`) need to be defined in `chassis.php`
- There is no config for the default bot: the first entry in the `bot` array is taken as the default bot. 


In a Controller you can use the `replyWith*` methods from `\Telegram\Bot\Answers\Answerable` and the `$this->getTelegram()` method to gain access to the underlying `Telegram\Bot\Api`.

#### Differences
Less `__call` magic
- the BotsManager does not magically call the default Api
- the Bot classes do not magically call the associated Api



return [
    
    'classes' => [
    	'bot' => App\Telegram\MyBot::class
        'user' => App\User::class,
        'storage' => App\User::class
    ],
    
    'sharedconfig' => [
    	'default' => [
    		'handler' => [
        		'*' => [
                	CatchallController::class, 'doSomething'
                ]
        	],
            'output' => [
            	Emojifier::class,
                MessageDefaultSetter::class
            ],
            'input' => [
            	TelegramLogger::class
            ]
        ]
    ],
    
    'pipeline' => [
   		'inbound' => [TelegramLogger::class, Emojifier::class],
        'outbound' => [Emojifier::class]
    ],
    
    'bots' => [
        'mybot' => [
            'username'  => 'track_it_bot',
            'token' => env('TELEGRAM_BOT_TOKEN', '1234'),
            'shared' => [
            	'default'
            ],
            'handler' => [
            	'message.text' => [
                	TextController::class, 'handleText', Bubbling::BEFORE
                ],
                'callback_query' => [
                	CallbackController::class, 'doit'
                ]
            ],            
        ],
    ],


	// See telegram-bot-sdk for the use of following keys

	'default' => 'mybot',    
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),    
    'http_client_handler' => null,
];

```



## Pipelining
You can define your own middleware which every inbound and outbound message gets piped through.

Input middleware included:
- TelegramLogger: logs every incoming telegram update

Output middleware included:
- Emojifier: Emojify from telegram-bot-sdk
- UpdateDefaultSetter: fills some update fields with sensible defaults if not set.
