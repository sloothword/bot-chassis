<?php

namespace Chassis\UserData;

use Chassis\Integration\StorageInterface;
use Log;
class UserDataRepository
{
    public function __construct($storage) {
        $this->storage = $storage;        
    }
    
    /**
     * 
     * @var StorageInterface
     */
    protected $storage;
    
    function save(UserData $userData)
    {
        $this->storage->save($userData->getKey(), $userData->getCollection()->all());
    }
    
    function load($update, $key = null)
    {
        $key = $this->getKey($update, $key);
        return new UserData($this, $key, $this->storage->load($key));
    }
    
    function delete($key)
    {
        Log::info("Deleted " .$key);
        $this->storage->delete($key);
    }
    
    function getKey($update, $key = null)
    {
        $message = $update->getMessage();
        $userId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        
        return $userId ."|" .$chatId ."|" .$key;
    }
}