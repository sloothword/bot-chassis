<?php

namespace Chassis\MetaData;

use Chassis\Integration\StorageInterface;
use Chassis\MetaData\ConversationData;
use Chassis\MetaData\MetaData;
use InvalidArgumentException;
use Telegram\Bot\Objects\BaseObject;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

/**
 * Generic Repository for MetaData
 */
class MetaDataRepository
{
    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage) {
        $this->storage = $storage;
    }

    /**
     * Stores retrieved MetaData in a cache for efficiency
     * @var MetaData[]
     */
    private $cache = [];

    /**
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Save MetaData to storage
     *
     * @param MetaData $metaData
     */
    public function save(MetaData $metaData)
    {
        $this->storage->save($metaData->getKey(), $metaData->all());
    }

    /**
     * Load MetaData associated to an Telegram object
     *
     * @param BaseObject $object
     * @return MetaData
     */
    public function load($object)
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

    /**
     * Delete MetaData from Repository
     * @param string $key
     */
    public function delete($key)
    {
        $this->storage->delete($key);
        unset($this->cache[$key]);
    }

    /**
     * Get key of object
     * @param BaseObject $object
     * @return string
     * @throws InvalidArgumentException
     */
    public function getKey(BaseObject $object)
    {
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
        throw new InvalidArgumentException('Cannot generate metadata key for class ' .get_class($object));
    }

    /**
     * Save all MetaData in Cache
     */
    public function saveAll(){
        foreach($this->cache as $metaData){
            $metaData->save();
        }
    }
}