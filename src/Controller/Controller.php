<?php

namespace Chassis\Controller;

use Chassis\Bot\ControllerBot;
use Telegram\Bot\Objects\Update;

class Controller {
    
    use \Telegram\Bot\Answers\Answerable;
    
    public function __construct(ControllerBot $bot, Update $update) {
        $this->update = $update;
        
        // TODO: Fix in Answerable
        $this->telegram = $bot->getTelegram();
    }
    
    function getTextWithoutCommand(){
        
    }
    
}
