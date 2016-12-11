<?php

namespace Chassis\Controller;

use Chassis\Bot\Bot;
use Chassis\MetaData\MetaDataRepository;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;

/**
 * Keep Controllers organized
 */
class ControllerBus
{

    /**
     * @var Controller[] Holds all controllers.
     */
    protected $controllers = [];

    /**
     * Instantiate Controller Bus.
     *
     * @param Bot $bot
     *
     * @throws TelegramSDKException
     */
    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Add multiple COntrollers
     *
     * @param Controller[] $controllers
     */
    public function addControllers(array $controllers)
    {
        foreach ($controllers as $controller) {
            $this->addController($controller);
        }
    }

    /**
     * Add one Controller
     *
     * Example config entry:
     * ['text', DummyController::class, 'dummy', Bubbling::BEFORE]
     *
     * @param array $controller Config entry for the Controller
     */
    public function addController(array $controller)
    {
        $hierarchy = $this->expandHierarchy($controller[0]);
        $class = $controller[1];

        $method = 'handle';
        $bubbling = Bubbling::NONE;

        if(isset($controller[2])){
            if(is_integer($controller[2])){
                $bubbling = $controller[2];
            }else{
                $method = $controller[2];
                if(isset($controller[3])){
                    $bubbling = $controller[3];
                }
            }
        }

        $key = $this->getControllerKey($hierarchy, $bubbling);

        if(!isset($this->controllers[$key])){
            $this->controllers[$key] = [];
        }
        $this->controllers[$key][] = [$class, $method];
    }

    /**
     * Get complete handler hierarchy by the shortkey
     *
     * @param string $shortKey
     * @return string
     */
    public function expandHierarchy($shortKey)
    {
        $longKeys = [
            '*' => 'update',
            'command' => 'update.message.text.command',
        ];

        $updateKeys = [
            'message', 'inline_query', 'chosen_inline_result', 'callback_query'
        ];

        $messageKeys = [
            'text', 'audio', 'document', 'photo', 'sticker', 'video',
            'voice', 'contact', 'location', 'venue', 'new_chat_member',
            'left_chat_member', 'new_chat_title', 'new_chat_photo',
            'delete_chat_photo', 'group_chat_created',
            'supergroup_chat_created', 'channel_chat_created',
            'migrate_to_chat_id', 'migrate_from_chat_id', 'pinned_message'
        ];

        foreach ($updateKeys as $k) {
            $longKeys[$k] = $longKeys['*'] ."." .$k;
        }

        foreach ($messageKeys as $k) {
            $longKeys[$k] = $longKeys['message'] ."." .$k;
        }

        if(array_key_exists($shortKey, $longKeys)){
            return $longKeys[$shortKey];
        }

        // Detect Commands
        if($shortKey[0] === '/'){
            return $longKeys['command'] .'.' .substr($shortKey, 1);
        }

        return $shortKey;
    }

    /**
     * String representation of a handler for easy bookkeeping
     *
     * @param string $hierarchy
     * @param int $bubbling
     * @return string
     */
    private function getControllerKey($hierarchy, $bubbling)
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

    private function getPartialHierarchy($parts, $depth)
    {
        return implode('.', array_slice($parts, 0, $depth));
    }

    /**
     * Get all handlers to call for this update
     *
     * @param Update $update
     * @return array
     */
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

    private function getHandler($handlerKeys, $depth, $bubbling)
    {
        $hierarchy = $this->getPartialHierarchy($handlerKeys, $depth);
        $key = $this->getControllerKey($hierarchy, $bubbling);

        if(isset($this->controllers[$key])){
            return $this->controllers[$key];
        }else{
            return [];
        }
    }

    private function callUpdateControllers(Update $update, $userData)
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

        for($depth = $maxDepth; $depth > 0; $depth--){
            $controller = $this->getHandler($handlerKeys, $depth, Bubbling::NONE);
            if(count($controller) > 0){
                $deepestFound = $depth;
                $this->callControllers($controller, $update, $userData);
                break;
            }
        }
    }

    private function callControllers($controllers, $update, $userData)
    {
        foreach($controllers as $controller){
            // Find right controller actions to call
            $name = $controller[0];
            $method = $controller[1];

            $this->callController($name, $method, $update, $userData);

        }
    }

    /**
     * Call a Controller directly
     *
     * @param string $name
     * @param string $method
     * @param Update $update
     * @param MetaDataRepository $userData
     */
    public function callController($name, $method, Update $update, MetaDataRepository $userData)
    {
        (new $name($this->bot, $update, $userData))->$method();
    }

    /**
     * Handles Inbound Updates
     *
     * @param Update $update
     * @param MetaDataRepository $userData
     */
    public function handler(Update $update, $userData)
    {
        $this->callUpdateControllers($update, $userData);
    }
}
