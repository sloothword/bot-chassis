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
    
    public function delete($key) {
        $this->redis->del($key);
    }

    public function load($key) {
        
        if(!$this->redis->exists($key)){
            return null;
        }
        return json_decode($this->redis->hget($key, 'data'), true);
    }
    
    public function flush(){
        $this->redis->flushdb();
    }

    public function save($key, $data) {
//        $this->redis->multi();
        $this->redis->hset($key, 'data', json_encode($data));
//        $this->redis->exec();
    }
}