Bot Chassis
====================
> Adds a fully featured layer upon the basic telegram API (telegram-bot-sdk) to drastically speed up telegram bot development

# Why?
The telegram-bot-sdk offers a stable API layer for bot development. However, for a fully featured bot many aspects are missing. Instead of jumping into defining the behaviour of the bot, you first need some routing system to handle arbitrary updates, a system to keep track of user conversations and persist associated data, and so on. This package aims to implemet this middle layer and enables you to go straight for the fun part!

# Feature Overview

- Build upon telegram-bot-sdk (all features included and easy access to the API layer)
- Use controller to easily handle any incoming updates
 - Process commands (`/text`)
 - Process any (other) text messages
 - Process inline and callback queries
 - React to Users joining and leaving chat etc.
- Use simple configuration to wire up and chain Controller


- Automatic binding of your applications user model to the telegram user.
- Out-of-the-box persistent data storage per conversation (linked to user and chat) or message (for callbak_queries)
- Support for conversations (chain of messages)


- Use Templating for your messages (MVC)
- Support for Pagination of messages with inline buttons

### Coming Soon
- Special support for commands (`/text arg1 arg2`) with argument parsing, and validation
- Listen for keywords in text messages
- Debug and test your bot offline and without telegram
- Pipelining: Add Input/Output Middleware
- Simple binding/handling of callback_queries
- Support for user/admin rights and controller authentication
- And much more...

# Installation

## with Laravel
Install Laravel
```
composer create-project --prefer-dist laravel/laravel mybot
```
Add `bot-chassis` to the composer.json:
```
composer require sloothword/bot-chassis
```
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

# Controller

## Basics
```php
<?php

namespace App\Telegram\Controller;

use TelegramBot\Controller;

class EchoController extends Controller
{
    
    public function sendEcho()
    {
    	$this->replyWithMessage([
        	'text' => 'You sent: ' .$this->getText()
        ]);
    }
}
```

## User Data
You can save data associated with the current discussion thread in a `Conversation` object. Calling `$this->getConversation()` returns the Conversation linked to the user and chat the update originated from. Save and load your data with `->addData($key, $value)` and `->getData($key)`.

## Conversations
With the Conversation object is also pretty straightforward to implement multi-step conversation threads. `Conversation->nextStep($class, $function)` defines which function should be called to handle the next text message of the current chat and user. This setting overrules the normal routing.

```php
// Example to handle multiple steps of a conversation
public function initialStep()
{
    // Retrieve the Conversation object
    $conversation = $this->getConversation();        
    
    // Ask the user
    $this->replyWithMessage([
    	'text' => 'What is your age?'
    ]);
    
    // Define the next step of the conversation
    $conversation->nextStep(self::class, 'askedForAge');
        
}

public function askedForAge()
{
    $conversation = $this->getConversation();        
    
    // Save the answer
    $conversation->addData('age', $this->getMessage()->getText());
    
    $this->replyWithMessage([
    	'text' => 'What is your name?'
    ]);
    
    $conversation->nextStep(self::class, 'askedForName');
}

public function askedForName
{
    $conversation = $this->getConversation();

	$name = $this->getMessage()->getText();

	$this->replyWithMessage([
		'text' => $name .' is ' .$conversation->getData('age') . 'years old';
	]);

	// Next update is routed normally
	$conversation->finish();
}
```
For such simple conversations you could also leverage the simpleStep method:

```php
// Example to handle multiple steps in one function
public function multiStepConversation()
{
    $conversation = $this->getConversation();        
    
    $simpleConversation = [
    	'age' => 'How old are you?',
        'name' => 'What is your name?'
    ];
    
    $dataComplete = $conversation->simpleStep($simpleConversation);
    
	if($dataComplete)
    {
		$this->replyWithMessage([
			'text' => $conversation->getData('age') .' is ' .$conversation->getData('age') . 'years old';
		]);
    }
}
```
Another method is using the conversation step counter:
```php
// Example to handle multiple steps in one function
public function multiStepConversation()
{
    $conversation = $this->getConversation();        
    
    // Read numbers
    if($conversation->getStep() > 0){
    	
        // Add number
        // TODO: Validation
        $sum = $conversation->getData('sum', 0) + $this->getMessage()->getText();
        
        $conversation->addData('sum', $sum);        
            
    }
    
    if($conversation->getStep() < 10){
    	$this->replyWithMessage([
        	'text' => 'Give me a number!'
        ]);
        $conversation->nextStep();
    }else{
    	$this->replyWithMessage([
        	'text' => 'The average is ' . $conversation->getData / 10;
        ]);
        
        $conversation->finalize();
    }
    
```


## Views
To separate the View and Controller concerns (MVC) and to simplify the creation of large or complicated messages the Blade templating engine is supported.
```php
@include('example.partial')
@if($otherData->count() > 0 )
    *List:*
    @foreach($otherData as $record)
        {{ $record->id }}    
    @endforeach
@else
	*There are no records*
@endif
```
> Note: Whitespace at the start or a line is ignored and can be used to format the view sourcecode. See XXX for the Blade documentation.


```php
	public function doViewResponse()
    {        
        $msg = $this->renderView('example.view', ['someData' => 'foo', 'otherData' => Mytable::all()]);
        
        $this->replyWithMessage([
        	'text' =>  $msg          
        ]);
    }
```


# Configuration
All configuration is still done inside the chassis.php config file.
> The configuration keys not eyplained here come from telegram-bot-sdk (XXX).

## telegram.php
```php
use  \App\Telegram\Controller\{CatchallController, TextController, CallbackController};
use \TeleBot\Bubbling;

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

## Handlers

You can use the handler array to configure, when to call which Controller methods.
It is possible to use either the short key as array index or the full hierarchy.


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

An optional third parameter defines the bubbling behaviour:

Key | Description | Example
--- | ------- | ------
`Bubbling::BEFORE` | Handler gets called before any more specific handler
`Bubbling::AFTER` | Handler gets called after any more specific handler
`Bubbling::NONE` (default) | Handler gets only called if there is no more specific handler available

##### Example:
```
'handler' => [
    ['*', ExampleController::class, 'handleAll', Bubbling::BEFORE ],
    ['message', ExampleController::class, 'handleMessage', Bubbling::AFTER ],
    ['text', ExampleController::class, 'handleText', Bubbling::NONE ],
    ['/XXX', ExampleController::class, 'handleXXX', Bubbling::NONE ]
]
```
Incoming Message | Called Handler
---------------- | --------------
Any update not containing a message | `handleAll()`
A message without text | `handleAll()` then `handleMessage()`
A text message | `handleAll()` then `handleText()` then `handleMessage()`
`/XXX` | `handleAll()` then `handleXXX()` then `handleMessage()`



## Pipelining
You can define your own middleware which every inbound and outbound message gets piped through.

Input middleware included:
- TelegramLogger: logs every incoming telegram update

Output middleware included:
- Emojifier: Emojify from telegram-bot-sdk
- UpdateDefaultSetter: fills some update fields with sensible defaults if not set.

# Artisan / CLI
The following artisan commands are available for your convenience:

Command | Description
-------------- | -----------
`chassis:handle`| Load and process all pending telegram updates. Optional argument specifies bot. Otherwise all bots get called.
`chassis:loop`| Read and process updates in a loop. Optional argument specifies bot. Otherwise all bots get called.
`chassis:message` | Open interactive console to emulate an telegram client and test your bot offline.

# Telegram-Bot-SDK
- config included
- `getTelegramApi()` returns `Api` class of telegram-bot-api

<!---
# Installation

# Setup

## Laravel / Lumen

## Plain PHP

# Folder structure example


```php
<?php

namespace App\Telegram\Controller;

use TelegramBot\Controller;

class ExampleController extends Controller
{
    
    public function doSomething()
    {
    	$arg = $this->parseArguments(
        	'arg1' => 'required|string',
        	'arg2' => 'required|integer'
        );
        
        $this->replyWithMessage([
        	'text' => 'The arguments were' .$arg->arg1 .' and ' .$arg->arg2
        ]);
    }
    
    public function doSomethingWithTheDatabase()
    {
    	$arg = $this->parseArguments(
        	'mymodel' => 'required|find:mytable',
        	'newtext' => 'required|integer'
        );
        
        $arg->mymodel->text = $arg->mymodel->newtext;
        $arg->mymodel->save();
        
        $this->replyWithMessage([
        	'text' => 'The text was changed!'
        ]);
    }
    
    public function doSomethingRequired()
    {
    	$arg = $this->parseArguments(
        	'arg1' => 'required|string'
        );
        
        if($arg->isValid('arg1'))
        {              
          	// Do something with the argument
        }else{
			$this->replyWithMessage([
            	'text' => 'Argument is invalid: ' .$arg->getError('arg1')
			]);
        }
    }
    
    
    
    public function doSomethingFuzzy()
    {
    	$arg = $this->parseArguments([
        	'day' => [
            	'weekday' =>  ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'weekend' =>  ['saturday', 'sunday']
            ]
        ]);
        
        $day = $this->getMessage()->getText();
        
        if($arg->day === 'weekday')
        {
        	$msg = $day .' is aworking day';
        }elseif($arg->day === 'weekend')
        {
        	$msg = $day .' is on the weekend';
        }else{
        	$msg = $day .' is no valid day';
        }
        
        
        $this->replyWithMessage([
          'text' =>  $msg          
        ]);        
    }
    
    public function doKeypadInput()
    {
    	$conversation->nextStep();
        
        if($conversation->getStep() == 0)
        {
        	$keys = [7, 8, 9, 4, 5, 6, 1, 2, 3, 0];
            $buttonsPerRow = 3;
            
        	$this->replyWithMessage([
            	'text' =>  $msg,
                'reply_markup' => Keyboard::fromArray($keys, $buttonsPerRow);
            ]);
        }else{
        	$this->replyWithMessage([
          		'text' =>  'You chose ' .$this->getMessage()->getText()          
        	]);
    }
}
```
-->