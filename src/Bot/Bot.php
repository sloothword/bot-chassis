<?php

namespace Chassis\Bot;

use League\Event\Emitter;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Holds the Telegram API and provides easy entry points to query for and process new updates.
 */
class Bot
{
    /* @TODO SDK: Refactor with traits when telegram-sdk releases v3 */
    //use Telegram;
    use \Chassis\Integration\Legacy\HasContainer;

    /** @var Api The telegram Api */
    protected $telegram = null;

    /**
     * Return the telegram Api
     *
     * @return Api
     */
    public function getTelegram()
    {
        return $this->telegram;
    }

    /**
     * Set the telegram Api Instance.
     *
     * @param Api $telegram
     *
     * @return Api
     */
    public function setTelegram(Api $telegram)
    {
        $this->telegram = $telegram;
    }
    // END TRAIT

    /**
     * Create a new Bot
     *
     * @param Api $api
     * @param array $config
     */
    public function __construct(Api $api, array $config)
    {

        // TODO SDK: Open issue on SDK: should be done in Api->__contruct()
        $api->setEventEmitter(new Emitter());

        $this->setTelegram($api);

        $this->readConfig($config);
    }

    /**
     * Handle a newly arrived update
     *
     * @param Update $update
     */
    public function processUpdate(Update $update)
    {
        // Basic bot does not handle updates. Overriden in subclasses.
    }

    /**
     * Parse in Bot configuration
     *
     * @param array $config
     */
    protected function readConfig(array $config)
    {

    }

    /**
     * Check for pending telegram updates. They get read, processed, confirmed and returned.
     *
     * @param boolean $webhook If true, read updates from webhook instead
     * @param array $params Parameters for Api->getUpdates()
     * @param boolean $process Set to false to disable processing of updates
     * @return Update[]
     */
    public function checkForUpdates($webhook = false, $params = [], $process = true)
    {
        if ($webhook) {
            $updates = [$this->getTelegram()->getWebhookUpdate()];
        } else {
            $updates = $this->getTelegram()->getUpdates($params);
        }

        $highestId = -1;
        foreach ($updates as $update) {
            if ($process) {
                $this->processUpdate($update);
            }
            $highestId = $update->getUpdateId();
        }

        if ($highestId != -1) {
            $this->confirmUpdate($highestId);
        }
        return $updates;
    }

    /**
     * Manually tell Telegram to not send the updates any more.
     *
     * @param int $highestUpdateId
     */
    public function confirmUpdate($highestUpdateId)
    {
        $params = [];
        $params['offset'] = $highestUpdateId + 1;
        $params['limit'] = 1;
        $this->getTelegram()->getUpdates($params, false);
    }
}
