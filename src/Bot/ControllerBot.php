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
    
    var $metaDataRepository;
        
    public function __construct($api, $config) {
        parent::__construct($api, $config);
        
        // TODO: read config
        $this->metaDataRepository = new \Chassis\MetaData\MetaDataRepository(new \Chassis\Integration\Redis\Storage());
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
        
        if($update->has('callback_query')){
            $metaData = $this->metaDataRepository->load($this->getMessage($update));
        }else{
            $metaData = $this->metaDataRepository->load($update);
        }
        
        if($metaData->has('controller')){
            $this->getControllerBus()->callController($metaData['controller']['name'], $metaData['controller']['method'], $update, $this->metaDataRepository);
        }else{
            // No user data -> route Controller like normal
            $this->getControllerBus()->handler($update, $this->metaDataRepository);
        }
        $this->metaDataRepository->saveAll();
    }    
    
    // TODO: put into SDK
    static function getMessage($update)
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