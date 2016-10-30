<?php

namespace Chassis\Controller;

trait Editable{
    
    function editMessage($params, $message = null, $ignoreErrors = false){
        if($message !== null){
            $params['message_id'] = $message->getMessageId();
            $params['chat_id'] = $message->getChat()->getId();
        }
        
        // TODO: Try/Catch is just for logging
        try{
            return $this->getTelegram()->editMessageText($params);
        }catch(\Telegram\Bot\Exceptions\TelegramResponseException $e){
            if($e->getResponseData()['description'] == "Bad Request: message is not modified"){
                \Log::info("Inline Update Error (not modified): ", $e->getResponseData());
            }elseif($e->getResponseData()['description'] == "Bad Request: Message text is empty"){
                \Log::info("Inline Update Error (empty): ", $e->getResponseData());
            }else{
                throw $e;
            }
            if(!$ignoreErrors){
                throw $e;
            }
        }
    }
}
