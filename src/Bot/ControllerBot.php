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
        $this->getControllerBus()->handler($update);
    }
}