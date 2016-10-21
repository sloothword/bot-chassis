<?php

namespace Chassis\Controller;

use Chassis\Bot\ControllerBot;
use Chassis\UserData\UserData;
use Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;
use View;
use Illuminate\Support\Collection;

class Controller {
    
    use \Telegram\Bot\Answers\Answerable;
    
    /**
     * @var ControllerBot
     */
    var $bot;
    
    /**
     *
     * @var UserData 
     */
    private $userData;
    
    function getBot()
    {
        return $this->bot;
    }
    
    public function __construct(ControllerBot $bot, Update $update, $userData) {
        $this->update = $update;
        $this->bot = $bot;
        $this->userData = $userData;
        
        // TODO: Fix in Answerable
        $this->telegram = $bot->getTelegram();
    }
    
    protected function nextConversationStep($name, $method){
        $this->getUserData()['controller'] = ['name' => $name, 'method' => $method];
    }
    
    protected function clearConversation(){
        $this->getUserData()->forget('controller');
    }
    
    function abortConversation(){
        $this->clearConversation();
        $this->userData->save();
        $this->getBot()->processUpdate($this->getUpdate());
    }
    
    /**
     * 
     * @return UserData
     */
    function getUserData()
    {
        return $this->userData->getCollection();
    }
    
    function setUserData($collection){
        $this->userData->replaceCollection($collection);
    }
    
    function clearUserData(){
        $this->userData->replaceCollection(new Collection());
    }
    
    function createReplyKeyboard($buttons){
        $count = count($buttons);
        $buttons = $this->resizeArray($buttons, 3, ceil($count / 3));
        Log::info("Buttons", [$buttons]);
        
        $reply_markup = Keyboard::make([
            'keyboard' => $buttons, 
            'resize_keyboard' => true, 
            'one_time_keyboard' => true
        ]);        
        
        return $reply_markup;        
    }
    
    function resizeArray($list, $x, $y){        
        $array = []; 

        $row = [];
        foreach ($list as $value) {
            $row[] = $value;
            if(count($row) == $x){
                $array[] = $row;
                $row = [];
            }
            if(count($array) == $y){
                return $array;
            }
        }
        if(count($row) > 0){
            $array[] = $row;
        }
        return $array;
    }
    
    /**
     * Example:
     * ['key1' => ['Question to Ask for Data1', 'getData1Suggestions']
     * @param array $data
     */
    function completeQuestionnaire($data){
        if($this->shallAbort()){
            $this->abortConversation();
            return;
        }
        
        $userData = $this->getUserData();
        if($userData->has('asked')){
            $userData[$userData['asked']] = $this->getText();
        }
        
        foreach ($data as $key => $requiredData){
            if($userData->has($key)){
            
            }else{
                $buttons = null;
                if(count($requiredData)>1){
                    $buttons = $this->{$requiredData[1]}($key);
                }
                $userData['asked'] = $key;
                $this->replyWithKeyboard(
                    $requiredData[0],
                    $buttons
                );
                return false;
            }
        }
        return true;
    }
    
    function replyWithView($view, $data = [], $buttons = [])
    {
        
        if(!$buttons){
            $keyboard = Keyboard::hide();
        }else{
            $keyboard = $this->createReplyKeyboard($buttons);            
        }
        
        $this->replyWithMessage([
            'text' => $this->renderView($view, $data), 
            'parse_mode' => 'markdown', 
            'reply_markup' => $keyboard
        ]);
    }
    
    function replyWithKeyboard($text, $buttons = null){
        
        
        if(!$buttons){
            $keyboard = Keyboard::hide();
        }else{
            $keyboard = $this->createReplyKeyboard($buttons);            
        }
        
        $this->replyWithMessage([
            'text' => $text, 
            'parse_mode' => 'markdown', 
            'reply_markup' => $keyboard
        ]);
    }
    
    function renderView($view, $data = [])
    {
        return str_replace("    ", "", View::make($view, $data)->render());
    }
    
    function execute($controller, $method, $update = null)
    {
        if($update === null){
            $update = $this->getUpdate();
        }else if($update instanceof Update){
            
        }else{
            $update = new Update(['message' => ['text' => $update]]);
        }
        
        $this->getBot()->getControllerBus()->callController($controller, $method, $update, $this->userData);
    }
}
