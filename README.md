Bot Chassis
====================
> **WARNING:** Currently in **ALPHA** state due to:
> - quickstart not tested and controller.md not finished
> - the telegram-bot-sdk currently used is no stable version
> - test coverage is low (but slowly growing)
> - integration options for plain php are lacking
> - no argument validation of Chassis API

## What?
Adds a fully featured layer upon the basic telegram API (telegram-bot-sdk) to drastically speed up telegram bot development

## Why?
The telegram-bot-sdk offers a stable API layer for bot development. However, for a fully featured bot many aspects are missing. Instead of jumping into defining the behaviour of the bot, you first need some routing system to handle arbitrary updates, a system to keep track of user conversations and persist associated data, and so on. This package aims to implement this middle layer and enables you to go straight for the fun part!

# Feature Overview

- Builds upon telegram-bot-sdk and makes all features easily accessible


- Use controller to easily handle any incoming updates. E.g
 - Process commands (`/text`)
 - Process any (other) text messages
 - Process inline and callback queries
 - React to Events like users joining and leaving chat etc.
- Use simple but powerful configuration to wire up and chain Controller

- Integrate with Laravel (and Redis as Backend) out of the box (see below for upcoming options)


- Out-of-the-box persistent storage of metadata
 - per conversation (linked to user and chat), message (for callback_queries), chat or user
 - Support for conversations (chain of messages) and helper to control message flow
 - Attach handler to your CallbackQueries just as easily

<!--
### Coming Soon
- More Integration options
 - Usable inside plain PHP (no Laravel) applications
 - Use Eloquent as storage backend or implement some simple interfaces for your solution of choice
 - Automatic binding of your applications user model to the telegram user.
- Support for dependency injection (of at least storage, user and bot implementations)
- Rapid message creation
 - MVC: Use (Blade) Templating for your messages
 - Helper for creating (inline) Buttons and Pagination
- Command argument parsing and validation 
- Commands decide whether to handle updates 
- "Special" commands for flow control `\abort` `\?` `\blank` 
- Listen for keywords in text messages 
- CLI Dummy Telegram Interface: Debug and test your bot offline 
- Pipelining: Add Input/Output Middleware 
- Support for user/admin rights and controller authentication 
- and much more

-->

# Documentation
See below for a [quickstart tutorial](#quickstart)

#### [Configuration](docs/Configuration.md) (WIP)
Installation methods, configuration options, framework integration and telegram-bot-sdk integration.

#### Controller (coming soon)
Routing of updates to Controller, Controller features, persistent MetaData storage

#### API (coming soon)

#### [Telegram Bot Api](https://core.telegram.org/bots)
A bot can only do as much as is defined in the official telegram bot API.

#### telegram-bot-sdk ([Github](https://github.com/irazasyed/telegram-bot-sdk), [Docs](https://telegram-bot-sdk.readme.io/docs))
bot-chassis is build upon telegram-bot-sdk with all features supported. Check the [Migration Guide](docs/Configuration#differences) if you are coming from telegram-bot-sdk.

# Feedback, Questions, Contributions
For all these you can use the [issue tracker](https://github.com/sloothword/bot-chassis/issues).

You could also find me and other bot developers at [Slack PHP Chat](https://phpchat.co/)
(Direct message irazasyed for access to #telegram-bot-sdk)

Pull requests are most appreciated if they use PSR-2 Coding Standards, have tests and can be pulled from a feature-branch.

# Quickstart
This tutorial uses Laravel and Redis and introduces the basic features `Controller` and `MetaData`. 

For more alternatives check the integration guide [Configuration].

### Installation
Install PHP7, Laravel and Redis. Configure Laravel to use your database and redis instance.

Tell composer to use the `telegram-bot-sdk` with `dev` stability (needed until we can use a stable version): 
```
composer require irazasyed/telegram-bot-sdk:@dev
```

Add `bot-chassis` to the composer.json:
```
composer require sloothword/bot-chassis
```
Add the LaravelServiceProvider in the app.php:
```
'providers' => [
        ...
        
        Chassis\Laravel\ChassisServiceProvider::class,
```
Publish the configuration (and migrations)
```
artisan vendor:publish
```
Open `config/chassis.php` and insert your bot credentials.

### Test Run
Send a command to the bot (commands `/echo`, `/delayed`, `/double`, see Chassis/Controller/EchoController.php), then call `artisan chassis:handle` to process it.

### Tutorial Bot
This tutorial bot will have three functions:
- The bot adds every integer messages in the chat.
- `/reset` sets the counter back to 0
- `/set` sets the counter to the next sent integer

### Controller
Create a new Controller under your application namespace and structure.
```php
<?php

namespace App\Controller;

use Chassis\Controller\Controller;

class CountController extends Controller
{
	public function add(){
    	// Add sent number to counter
    }
    
    public function reset(){
    	// Reset the counter
    }
    
    public function set(){
    	// Set the counter to the next integer message
    }
}
```
and add it to the controller list in chassis.php (and remove the others):
```
'bots' => [
        'mybot' => [
            'username'  => 'BOT-USERNAME',
            'token' => env('TELEGRAM_BOT_TOKEN', 'BOT-TOKEN'),
            'shared' => [],
            'controllers' => [
            	['text', App\Controller\CountController::class, 'add'],
            	['/reset', App\Controller\CountController::class, 'reset'],
                ['/set', App\Controller\CountController::class, 'set']
            ],          
        ],
    ],
```
Now everytime you send the `/reset` command to the Bot the `reset()`method of `CountController`is called. Everytime we send text to the bot (which is no command) the `add()` method is called.

### Learn to count
The MetaData you get by calling `$this->getConversationData()` (shortcut for `$this->getMetaData($this->getUpdate()`) in any Controller is persisted by the Chassis framework and linked to the current conversation. Using this here means that each user and chat has its own counter. 

We could also use the MetaData object linked to the user (`$this->getMetaData($this->getUser())`) to get each user a single counter across all chats. With `$this->getMetaData($this->getChat())` all users of a chat could count together.

```
class CountController extends Controller
{
    
    public function add(){
    	$value = intval($this->getText());
    
    	$conversationData = $this->getConversationData();
        
        $counter = $conversationData->get('count', 0);        
        
        $conversationData['count'] = $counter + $value;
        
        $this->replyWithMessage(['text' => 'New value: ' .$conversationData['count']]);
        
    }
    
    public function reset(){
    	$this->getConversationData()->forget('count');
        $this->replyWithMessage(['text' => 'Reset counter']);
    }
}


```
Notes:
- You do not need to be concerned about saving back the changed MetaData as it will be done automatically.
- MetaData extends Illuminate/Support/Collection (see `get()`, `has()` and `forget()` methods).
- `$this->getText()` is a shortcut for `$this->getUpdate()->getMessage()->getText()`
- Controller uses the `Telegram\Bot\Answers\Answerable` trait, so you can use all the `reply*` methods

### Input and Conversation Flow
For the `/set` command we need to do three things:
- Instruct the user to message the new count
- Define which controller method should handle the response
- In that method set the counter to the message text

```
public function set(){	
	$this->replyWithMessage(['text' => 'Insert new value:']);
    $this->getConversationData()->nextConversationStep(self::class, 'saveCounter');
}

public function saveCounter(){
	$value = intval($this->getText());
    $conversationData = $this->getConversationData();
    $conversationData['count'] = $value;
    $this->replyWithMessage(['text' => 'Set counter to ' .$value]);
    $conversationData->clearConversation();
}
```
Pay attention to the last line: as we set the next handling method to `saveCounter()` the bot will always call that same method untill we tell him not to `->clearConversation()`.

> Note: This behaviour will change soon.

### Finish
Play around with your finished bot.

Also try `artisan chassis:handle --loop` to continously run your bot.

For more features and detailed information check out the documentation [Documentation](#documentation).