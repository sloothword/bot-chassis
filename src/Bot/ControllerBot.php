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
    
    var $userDataRepository;
        
    public function __construct($api, $config) {
        parent::__construct($api, $config);
        
        // TODO: read config
        $this->userDataRepository = new \Chassis\UserData\UserDataRepository(new \Chassis\Integration\Redis\Storage());
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
            $userData = $this->userDataRepository->load($update, $key);
        }else{
            $userData = $this->userDataRepository->load($update);
        }
        
        $data = $userData->getCollection();
        
        
        if($data->has('controller')){
            $this->getControllerBus()->callController($data['controller']['name'], $data['controller']['method'], $update, $userData);
        }else{
            // No user data -> route Controller like normal
            $this->getControllerBus()->handler($update, $userData);
        }
        $userData->save();
    }    
    
    function getMessage($update)
    {
        switch ($update->detectType()){
            case 'message':
                $message = $update->getMessage();
                break;
            case 'callback_query':
                $message = $update->getCallbackQuery()->getMessage();
                break;
        }
        return $message;
    }
}