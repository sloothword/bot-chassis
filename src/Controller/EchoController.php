<?php

namespace Chassis\Controller;

use Telegram\Bot\Objects\Update;
use Chassis\Bot\Bot;

class EchoController extends Controller{      
    
    public function once(){
        $this->replyWithMessage([
            'text' => $this->getUpdate()->getMessage()->getText()
        ]);
    }
    
    public function twice(){
        $this->replyWithMessage([
            'text' => $this->getUpdate()->getMessage()->getText() . $this->getUpdate()->getMessage()->getText()
        ]);
    }
}
