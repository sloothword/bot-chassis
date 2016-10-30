<?php

namespace Chassis\Controller;

use Chassis\Bot\ControllerBot;
use Chassis\MetaData\ConversationData;
use Chassis\MetaData\MetaDataRepository;
use Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;
use View;
use Chassis\Helper\Pagination;

class Controller {
    
    use \Telegram\Bot\Answers\Answerable;
    use Editable;
    
    /**
     * @var ControllerBot
     */
    var $bot;
    
    /**
     *
     * @var MetaDataRepository
     */
    private $metaDataRepository;
    
    function getBot()
    {
        return $this->bot;
    }
    
    public function __construct(ControllerBot $bot, Update $update, $metaDataRepository) {
        $this->update = $update;
        $this->bot = $bot;
        $this->metaDataRepository = $metaDataRepository;
        
        // TODO: Fix in Answerable
        $this->telegram = $bot->getTelegram();
    }
    
    function getMetaData($object){
        return $this->metaDataRepository->load($object);
    }    
    
    /**
     * 
     * @return ConversationData
     */
    function getConversationData(){
        Log::info("Conversation", $this->getMetaData($this->getUpdate())->all());
        Log::info("Conversation Class", [get_class($this->getMetaData($this->getUpdate()))]);
        return $this->getMetaData($this->getUpdate());
    }
    
    /** 
     * @return MessageData
     */
    function getMessageData($message){
        return $this->getMetaData($message);
    }
    
    function createReplyKeyboard($buttons, $inline = false, $cols = null){
        
        $count = count($buttons);
        if($cols == null){
            $cols = 3;
        }
        
        $rows = []; $row = [];
        foreach ($buttons as $button) {
            
            if($inline){
                $row[] = Keyboard::inlineButton(['text' => $button, 'callback_data' => $button]);
            }else{
                $row[] = Keyboard::button(['text' => $button]);
            }
            
            if(count($row) == $cols){
                $rows[] = $row;
                $row = [];
            }
        }
        if(count($row)>0){
            $rows[] = $row;
        }
        
        $property = 'keyboard';
        if ($inline) {
            $property = 'inline_keyboard';
        }
        
        $k = Keyboard::make([$property => $rows]);
        $k->setResizeKeyboard(true);
        $k->setOneTimeKeyboard(true);
        Log::info("Keyboard created", $k->all());
        return $k;        
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
        
        $userData = $this->getConversationData();
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
                $this->replyWithMessage(
                    $this->createReply(null, $requiredData[0], $buttons)
                );                
                return false;
            }
        }
        return true;
    }   
    
    function createReply($view = null, $data = null, $buttons = null, $params = []){
        
        // If view set --> render view
        if($view !== null){
            $params['text'] = $this->renderView($view, $data);
            $params['parse_mode'] = 'markdown';
        }elseif($data != null){
            $params['text'] = $data;
        }
        
        if($buttons === null){
            $keyboard = Keyboard::hide();
        }elseif($buttons instanceof Keyboard){
            // Do nothing
            $keyboard = $buttons;
        }elseif(is_array ($buttons) or $buttons instanceof \ArrayAccess){
            if(isset($buttons['buttons'])){
                $inline = isset($buttons['inline']) ? $buttons['inline'] : false;
                $cols = isset($buttons['cols']) ? $buttons['cols'] : null;
                $keyboard = $this->createReplyKeyboard($buttons['buttons'], $inline, $cols);
            }else{
                $keyboard = $this->createReplyKeyboard($buttons);
            }
        }
        $params['reply_markup'] = $keyboard;
        
        Log::info('Reply', $params);
        return $params;
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
        
        $this->getBot()->getControllerBus()->callController($controller, $method, $update, $this->metaDataRepository);
    }
    
    function getPagination($perPage = 5, $count = -1){
        $metaData = $this->getMessageData(ControllerBot::getMessage($this->getUpdate()));
        
        $page = $metaData->has('page') ? $metaData['page'] : 0;
        $perPage = $metaData->has('perPage') ? $metaData['perPage'] : $perPage;
                
        return new Pagination($page, $perPage, $count);
    }
}
