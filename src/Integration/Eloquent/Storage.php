<?php

namespace Chassis\Integration\Eloquent;

use Chassis\Integration\StorageInterface;
use Log;

class Storage implements StorageInterface
{

    public function delete($key) {
        BotStorage::where('key', '=', $key)
            ->delete();
    }

    public function load($key) {
        $model = BotStorage::where('key', '=', $key)
            ->first();

        if($model){
            return json_decode($model->data, true);
        }
        return null;
    }

    public function save($key, $data) {
        BotStorage::updateOrCreate(
            ['key'=> $key],
            ['data' => json_encode($data)]);
    }

    public function flush()
    {
        // TODO: improve performance
        BotStorage::delete();
    }
}