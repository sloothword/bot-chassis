<?php
namespace Chassis\MetaData;

class MessageData extends MetaData{
    
    public function setCallbackHandler($name, $method){
        $this['controller'] = ['name' => $name, 'method' => $method];
    }
    
    function clearCallbackHandler(){
        $this->forget('controller');
    }
}