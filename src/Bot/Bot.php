<?php

// Hold API
// Register Event Handler
// Propagate to Controller

namespace Chassis\Bot;

use League\Event\Emitter;
use Log;
use Telegram\Bot\Api;
use Telegram\Bot\Events\UpdateWasReceived;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Traits\Telegram;


class Bot 
{
    // TODO: Not yet released
    //use Telegram;
    
    /** @var Api Holds the Super Class Instance. */
    protected $telegram = null;

    /**
     * Returns Super Class Instance.
     *
     * @return Api
     */
    public function getTelegram()
    {
        return $this->telegram;
    }

    /**
     * Set Telegram Api Instance.
     *
     * @param Api $telegram
     *
     * @return $this
     */
    public function setTelegram(Api $telegram)
    {
        $this->telegram = $telegram;

        return $this;
    }
    
    // END TRAIT
    
    public function __construct(Api $api, $config) {
        // TODO: Open issue on SDK
        $api->setEventEmitter(new Emitter());
        
        $this->setTelegram($api);        
        
        $this->readConfig($config);
        
//        Log::info("Constructed Bot");
//        $this->getTelegram()->getEventEmitter()->addListener(UpdateWasReceived::class, function (UpdateWasReceived $event) {
//            \Log::info("EVENT");
//            $this->processUpdate($event->getUpdate());
//        });
    }
    
    /**
     * Magically pass methods to the api.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
//    public function __call($method, $parameters)
//    {
//        return call_user_func_array([$this->getTelegram(), $method], $parameters);
//    }
    
    /**
     * Check update object for a command and process.
     *
     * @param Update $update
     */
    public function processUpdate(Update $update)
    {
        
    }
    
    function readConfig($config)
    {
        
    }
    
    /**
     * Checks for pending telegram updates. If available, they get read, processed, confirmed and returned.
     *
     * @param boolean $webhook Signals to read update from webhook.
     * @param array $params See Api->getUpdates()
     * @return Update[]
     */
    public function checkForUpdates($webhook = false, $params = [])
    {
        Log::info("check for updates");
        if ($webhook) {
            $updates = $this->getTelegram()->getWebhookUpdates();
        } else {
            $updates = $this->getTelegram()->getUpdates($params);
        }
        
        $highestId = -1;
        foreach ($updates as $update) {
            $this->processUpdate($update);
            $highestId = $update->getUpdateId();
        }
        
        if ($highestId != -1) {
            $this->confirmUpdate($highestId);
        }
        return $updates;
    }
    
    public function confirmUpdate($highestUpdateId)
    {
        $params = [];
        $params['offset'] = $highestUpdateId + 1;
        $params['limit'] = 1;
        $this->getTelegram()->getUpdates($params, false);
    }
}