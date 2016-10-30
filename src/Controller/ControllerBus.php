<?php

namespace Chassis\Controller;

use Chassis\Bot\Bot;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;
use Log;

// Call correct Controller
// Keep and handle Controllers

class ControllerBus
{
    
    /**
     * @var Controllers[] Holds all controllers.
     */
    protected $controllers = [];    

    /**
     * Instantiate Command Bus.
     *
     * @param Api|null $telegram
     *
     * @throws TelegramSDKException
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

    
    public function addControllers(array $controllers)
    {
        foreach ($controllers as $controller) {
            $this->addController($controller);
        }
    }
    
    public function addController($controller)
    {
        // ['text', DummyController::class, 'dummy', Bubbling::BEFORE]
        
        $hierarchy = $this->expandHierarchy($controller[0]);
        $class = $controller[1];
        $method = $controller[2];
        $bubbling = isset($controller[3]) ? $controller[3] : Bubbling::NONE;
        
        $key = $this->getControllerKey($hierarchy, $bubbling);
        
        if(!isset($this->controllers[$key])){
            $this->controllers[$key] = [];
        }
        $this->controllers[$key][] = [$class, $method];
    }
    
    function expandHierarchy($shortKey)
    {

        // TODO: complete
        $longKeys = [
            '*' => 'update',
            'message' => 'update.message',
            'text' => 'update.message.text',
            'command' => 'update.message.text.command'
        ];
        
        if(array_key_exists($shortKey, $longKeys)){
            return $longKeys[$shortKey];
        }
        
        if($shortKey[0] === '\\'){
            return $longKeys['command'] .'.' .substr($shortKey, 1);
        }
        
        return $shortKey;
    }
    
    function getControllerKey($hierarchy, $bubbling)
    {
        switch($bubbling){
            case Bubbling::BEFORE:
                return "<" .$hierarchy;
            case Bubbling::AFTER:
                return ">" .$hierarchy;
            default:
                return "?" .$hierarchy;
        }
    }
    
    function getPartialHierarchy($parts, $depth)
    {        
        return implode('.', array_slice($parts, 0, $depth));
    }
    
    function parseConfig($config)
    {
        $this->addControllers($config['controllers']);
    }
    
    // Returns full hierarchy to call for this update
    public static function getUpdateHierarchy(Update $update)
    {        
        $updateType = $update->detectType();
        
        $key = ['update', $updateType];
        
        if($updateType === 'message')
        {
            $message = $update->getMessage();
            $messageType = $message->detectType();
            $key[] = $messageType;
            
            if($messageType === 'text' 
                    && preg_match('/^\/([^\s@]+)@?(\S+)?\s?(.*)$/s', $update->getMessage()->getText(), $matches))
            {
                $key[] = 'command';
                $key[] = $matches[1];
            }            
        }
        return $key;
    }
    
    // Return handler
    function getHandler($handlerKeys, $depth, $bubbling)
    {
        $hierarchy = $this->getPartialHierarchy($handlerKeys, $depth);
        $key = $this->getControllerKey($hierarchy, $bubbling);
        
        if(isset($this->controllers[$key])){
            return $this->controllers[$key];
        }else{
            return [];
        }        
    }
    
    function callUpdateControllers(Update $update, $userData)
    {
        $handlerKeys = $this->getUpdateHierarchy($update);
        $maxDepth = count($handlerKeys);
        
        $deepestFound = 0;
        for($depth = 1; $depth <= $maxDepth; $depth++){
            $controller = $this->getHandler($handlerKeys, $depth, Bubbling::BEFORE);
            if(count($controller) > 0){
                $deepestFound = $depth;
                $this->callControllers($controller, $update, $userData);
            }
        }
        
        
        for($depth = $maxDepth; $depth > 0; $depth--){
            $controller = $this->getHandler($handlerKeys, $depth, Bubbling::AFTER);
            if(count($controller) > 0){
                $deepestFound = $depth;
                $this->callControllers($controller, $update, $userData);
            }
        }
        
        for($depth = $deepestFound; $depth <= $maxDepth; $depth++){
            $controller = $this->getHandler($handlerKeys, $depth, Bubbling::NONE);
            if(count($controller) > 0){
                $deepestFound = $depth;
                $this->callControllers($controller, $update, $userData);
                break;
            }
        }        
    }
    
    function callControllers($controllers, $update, $userData)
    {
        foreach($controllers as $controller){
            // Find right controller actions to call
            $name = $controller[0];
            $method = $controller[1];

            $this->callController($name, $method, $update, $userData);
            
        }
    }
    
    public function callController($name, $method, $update, $userData)
    {
        (new $name($this->bot, $update, $userData))->$method();
    }
    
    /**
     * Handles Inbound Updates
     *
     * @param $update
     */
    public function handler(Update $update, $userData)
    {
        $this->callUpdateControllers($update, $userData);
    }
}
