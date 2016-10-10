<?php

namespace Chassis\Controller;

use Chassis\Bot\ControllerBot;
use Telegram\Bot\Objects\Update;

class Controller {
    
    use \Telegram\Bot\Answers\Answerable;
    
    var $bot;
    
    public function __construct(ControllerBot $bot, Update $update, $data = null) {
        $this->update = $update;
        $this->bot = $bot;
        $this->data = $data;
        
        // TODO: Fix in Answerable
        $this->telegram = $bot->getTelegram();
    }
    
    protected function setNextController($name, $method, $data = []){
        
        $data['controller'] = ['name' => $name, 'method' => $method];        
        
        $this->bot->setUserData($this->getUpdate(), $data);        
    }
    
    protected function clearNextController(){
        $this->bot->clearUserData($this->getUpdate());
    }
    
    function getUserData()
    {
        return $this->data;
    }
    
}
