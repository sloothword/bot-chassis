<?php

namespace Chassis\Integration\Eloquent;

use Chassis\Integration\StorageInterface;
use Log;

class Storage implements StorageInterface
{
    
    public function delete($userId, $chatId, $key) {
        BotStorage::where('user_id', '=', $userId)
            ->where('chat_id', '=', $chatId)
            ->where('key', '=', $key)
            ->delete();
    }

    public function load($userId, $chatId, $key) {
        Log::info('Load', [$userId, $chatId, $key]);
        $model = BotStorage::where('user_id', '=', $userId)
            ->where('chat_id', '=', $chatId)
            ->where('key', '=', $key)
            ->first();
        
        if($model){
            Log::info('Loaded', json_decode($model->data, true));
            return json_decode($model->data, true);
        }
        return null;        
    }

    public function save($userId, $chatId, $key, $data) {
        BotStorage::updateOrCreate(
            ['user_id'=>$userId, 'chat_id' => $chatId, 'key'=> $key],
            ['data' => json_encode($data)]);
    }
}