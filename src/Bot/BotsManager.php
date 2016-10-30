<?php

// extends BotManager
// creates Bot (with Controller support)

namespace Chassis\Bot;

use GuzzleHttp\Client;
use Log;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

class BotsManager
{
    /** @var BotsManager */
    protected $botsManager;
    
    /** @var array */
    protected $config;
    
    /** @var Bot[] The active bot instances. */
    protected $bots = [];    
    
    public function __construct($telegramConfig, $chassisConfig)
    {
        if(isset($chassisConfig['telegram'])){
            $telegramConfig = array_merge($telegramConfig, $chassisConfig['telegram']);
        }
        
        $telegramConfig['bots'] = $this->convertBotsToTelegramConfig($chassisConfig['bots']);
        Log::info("Config 'Telegram'", $telegramConfig);
        Log::info("Config 'Chassis'", $chassisConfig);
        
        $this->config = $chassisConfig;
        $this->botsManager = new \Telegram\Bot\BotsManager($telegramConfig);
    }
    
    function convertBotsToTelegramConfig($chassisConfig)
    {
        // TODO: remove unused keys
        
//        'bots' => [
//        'common' => [
//            'username'  => 'MyTelegramBot',
//            'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
//            'commands' => [
//                Acme\Project\Commands\MyTelegramBot\BotCommand::class
//            ],
//        ],
        
//        'bots' => [
//        'mybot' => [
//            'username'  => 'INSERT BOT USERNAME',
//            'token' => env('TELEGRAM_BOT_TOKEN', 'INSERT TELEGRAM TOKEN'),
//            'shared' => [],
//            'handler' => [
//                'text' => [
//                    DummyController::class, 'dummy', Bubbling::BEFORE
//                ]
//            ],            
//        ],

        
        return $chassisConfig;
    }
    
    public function bot($name = null)
    {
        $name = $name ?: $this->getDefaultBot();

        if (!isset($this->bots[$name])) {
            $this->bots[$name] = $this->makeBot($name);
        }

        return $this->bots[$name];
    }
    
    public function getDefaultBot()
    {
        return array_keys($this->config['bots'])[0];
    }
    
    /**
     * Make the bot instance.
     *
     * @param string $name
     *
     * @return Bot
     */
    public function makeBot($name, $api = null)
    {
        Log::info("Make Bot", [$name]);
        if($api === null){
            $api = $this->botsManager->bot($name);
        }
        
        $config = $this->getBotConfig($name);
        
        $botClass = $this->config['classes']['bot'];
        return new $botClass($api, $config);
    }
    
    function getBotConfig($name)
    {
        // TODO: include shared config
        return $this->config['bots'][$name];
    }
}