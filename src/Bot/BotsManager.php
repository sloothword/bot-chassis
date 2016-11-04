<?php

namespace Chassis\Bot;

use Chassis\Bot\Bot;
use Chassis\Bot\BotsManager as ChassisBotsManager;
use Telegram\Bot\BotsManager as TelegramBotsManager;

/**
 * Extends the capabilities of the base BotsManager to create Bots.
 * @TODO SDK: Put functionality into telegram SDK
 */
class BotsManager
{

    /** @var ChassisBotsManager */
    protected $botsManager;

    /** @var array */
    protected $config;

    /** @var Bot[] The active bot instances. */
    protected $bots = [];

    /**
     * Initialize both BotsManager and setup config
     *
     * @param array $telegramConfig Array of the telegram config file
     * @param array $chassisConfig Array of the Chassis config file
     *
     * @TODO: Reorder arguments and possibly remove telegramConfig
     */
    public function __construct(array $telegramConfig, array $chassisConfig)
    {
        // Extract inlcude telegram config keys
        if (isset($chassisConfig['telegram'])) {
            $telegramConfig = array_merge($telegramConfig, $chassisConfig['telegram']);
        }

        // Extract bot config
        /* @TODO: remove unused keys and do cleanup */
        $telegramConfig['bots'] = $chassisConfig['bots'];

        $this->config = $chassisConfig;
        $this->botsManager = new TelegramBotsManager($telegramConfig);
    }

    /**
     * Returns named bot instance
     *
     * @param string $name Name of bot. Leave blank for default bot.
     * @return Bot
     */
    public function bot($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultBot();
        }

        if (!isset($this->bots[$name])) {
            $this->bots[$name] = $this->makeBot($name);
        }

        return $this->bots[$name];
    }

    /**
     * The default bot is the first defined in the config
     *
     * @return string default bot name
     */
    public function getDefaultBot()
    {
        return array_keys($this->config['bots'])[0];
    }

    /**
     * Make the bot instance.
     *
     * @param string $name
     * @param Api $api Override the used Api instance
     *
     * @return Bot
     */
    public function makeBot($name, $api = null)
    {
        if ($api === null) {
            $api = $this->botsManager->bot($name);
        }

        $config = $this->getBotConfig($name);

        $botClass = $this->config['classes']['bot'];
        return new $botClass($api, $config);
    }

    /**
     * Read config for a bot
     *
     * @param string $name Name of Bot
     * @return array
     */
    private function getBotConfig($name)
    {
        /** @TODO: Read and include shared config keys */
        return $this->config['bots'][$name];
    }
}
