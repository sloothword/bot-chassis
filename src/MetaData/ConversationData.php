<?php
namespace Chassis\MetaData;

class ConversationData extends MetaData{
    
    public function nextConversationStep($name, $method){
        $this['controller'] = ['name' => $name, 'method' => $method];
    }
    
    function clearConversation(){
        $this->forget('controller');
    }
}