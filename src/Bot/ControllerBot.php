<?php

namespace Chassis\Bot;

use Chassis\Bot\Bot;
use Chassis\Controller\ControllerBus;
use Telegram\Bot\Commands\CommandBus;
use Telegram\Bot\Objects\Update;
use Log;

class ControllerBot extends Bot
{
    /**
     * @var ControllerBus Telegram Command Bus.
     */
    protected $controllerBus = null;
    
    /**
     * 
     * @var StorageInterface
     */
    protected $storage;
    
    public function __construct($api, $config) {
        parent::__construct($api, $config);
        
        // TODO: read config
        $this->storage = new \Chassis\Integration\Redis\Storage();
    }
    
    public function readConfig($config)
    {        
        $this->controllerBus = new ControllerBus($this);        

        // Register Commands
        $this->controllerBus->addControllers($config['controllers']);
    }
        
    /**
     * Returns SDK's Command Bus.
     *
     * @return ControllerBus
     */
    public function getControllerBus()
    {
        return $this->controllerBus;
    }
    
    /**
     * Check update object for a command and process.
     *
     * @param Update $update
     */
    public function processUpdate(Update $update)
    {
        Log::info("Process UPDATE");
        
        // Check for existing user Data        
        if($update->isType('callback_query')){
            $key = $update->getCallbackQuery()->getData();
            $data = $this->getUserData($update, $key);
        }else{
            $data = $this->getUserData($update);
        }
            
        if(isset($data['controller'])){
            $this->getControllerBus()->callController($data['controller']['name'], $data['controller']['method'], $update, $data);
        }else{
            // No user data -> route Controller like normal
            $this->getControllerBus()->handler($update);
        }
    }
    
    function getStorageKeys($update)
    {
        switch ($update->detectType()){
            case 'message':
                $message = $update->getMessage();
                break;
            case 'callback_query':
                $message = $update->getCallbackQuery()->getMessage();
                break;
        }
        $userId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        
        return [$userId, $chatId];
    }
    
    function setUserData($update, $data)
    {
        $keys = $this->getStorageKeys($update);
        $this->storage->save($keys[0], $keys[1], null, $data);
    }
    
    function getUserData($update, $key = null)
    {
        $keys = $this->getStorageKeys($update);        
        return $this->storage->load($keys[0], $keys[1], $key);
    }
}