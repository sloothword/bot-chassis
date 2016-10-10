<?php

namespace Chassis\Integration\Redis;

use Chassis\Integration\StorageInterface;
use Log;

class Storage implements StorageInterface
{
    var $redis;
    
    public function __construct() {
        $this->redis = resolve('redis');        
    }
    
    function getKey($userId, $chatId, $key)
    {
        return $userId.$chatId.$key;
    }
    
    public function delete($userId, $chatId, $key) {
        $this->redis->del($this->getKey($userId, $chatId, $key));
    }

    public function load($userId, $chatId, $key) {
        Log::info('Load', [$userId, $chatId, $key]);
        
        $redisKey = $this->getKey($userId, $chatId, $key);
        if(!$this->redis->exists($redisKey)){
            return null;
        }
        Log::info('Loaded', [$this->redis->hget($redisKey, 'data')]);
        return json_decode($this->redis->hget($redisKey, 'data'), true);
    }

    public function save($userId, $chatId, $key, $data) {
//        $this->redis->multi();
        $this->redis->hset($this->getKey($userId, $chatId, $key), 'data', json_encode($data));
//        $this->redis->exec();
    }
}