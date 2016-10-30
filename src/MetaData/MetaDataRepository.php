<?php

namespace Chassis\MetaData;

use Chassis\Integration\StorageInterface;
use Chassis\MetaData\ConversationData;
use Chassis\MetaData\MetaData;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;
use Log;


class MetaDataRepository
{
    public function __construct($storage) {
        $this->storage = $storage;        
    }
    
    private $cache = [];
    
    /**
     * 
     * @var StorageInterface
     */
    protected $storage;
    
    function save(MetaData $metaData)
    {
        $this->storage->save($metaData->getKey(), $metaData->all());
    }
    
    function load($object)
    {        
        $key = $this->getKey($object);
        
        if(!isset($this->cache[$key])){
            if($object instanceof Update){
                $metaData = new ConversationData($this->storage->load($key));
            }elseif($object instanceof Message){
                $metaData = new MessageData($this->storage->load($key));
            }else{
                $metaData = new MetaData($this->storage->load($key));
            }
            $metaData->connect($this, $key);
            $this->cache[$key] = $metaData;
        }        
        return $this->cache[$key];
    }    
    
    function delete($key)
    {
        Log::info("Deleted " .$key);
        $this->storage->delete($key);
        unset($this->cache[$key]);
    }
    
    function getKey($object)
    {
        Log::info("Classes", [get_class(new Update([])), get_class($object)]);
        if($object instanceof Update){
            
            // Link to Conversation
            $message = $object->getMessage();
            $userId = $message->getFrom()->getId();
            $chatId = $message->getChat()->getId();
            return "U:" .$userId ."|" .$chatId;
            
        }elseif($object instanceof Message){
            
            // Link to Message (e.g. callback data)            
            return "M:" .$object->getMessageId();
            
        }elseif($object instanceof Chat){
            
            // Link to Chat            
            return "C:" .$object->getId();
            
        }elseif($object instanceof User){
            
            // Link to User      
            return "U:" .$object->getId();
        }
        throw new \InvalidArgumentException('Cannot generate metadata key for class ' .get_class($object));
    }
    
    function saveAll(){
        foreach($this->cache as $metaData){
            $metaData->save();
        }
    }
}