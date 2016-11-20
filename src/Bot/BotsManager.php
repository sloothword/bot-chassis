<?php

namespace Chassis\Bot;

use Chassis\Bot\Bot;
use Chassis\Bot\BotsManager as ChassisBotsManager;
use Chassis\Integration\Legacy\HasContainer;
use Chassis\Integration\Redis\Storage as RedisStorage;
use Chassis\Integration\StorageInterface;
use Chassis\MetaData\MetaDataRepository;
use Illuminate\Contracts\Container\Container;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager as TelegramBotsManager;

/**
 * Extends the capabilities of the base BotsManager to create Bots.
 * @TODO SDK: Put functionality into telegram SDK
 */
class BotsManager
{
    use HasContainer;

    /** @var ChassisBotsManager */
    protected $botsManager;

    /** @var array */
    protected $config;

    /** @var Bot[] The active bot instances. */
    protected $bots = [];

    /**
     * Initialize both BotsManager and setup config
     *
     * @param array $chassisConfig Array of the Chassis config file
     * @param array $telegramConfig Array of the telegram config file
     *
     */
    public function __construct(array $chassisConfig, array $telegramConfig, Container $container = null)
    {
        // Extract telegram config keys
        if (isset($chassisConfig['telegram'])) {
            $telegramConfig = array_merge($telegramConfig, $chassisConfig['telegram']);
        }

        // Extract bot config
        /* @TODO: remove unused keys and do cleanup */
        $telegramConfig['bots'] = $chassisConfig['bots'];

        $this->config = $chassisConfig;
        \Log::info($telegramConfig);
        $this->botsManager = new TelegramBotsManager($telegramConfig);

        if($container != null){
            $this->setContainer($container);
        }
        if(isset($chassisConfig['classes'])){
            $this->initializeContainer($chassisConfig['classes']);
        }

    }

    private function initializeContainer($classes)
    {
        if(!$this->hasContainer()){
            $this->setContainer(new \Illuminate\Container\Container());
        }

        // Default Implementations
        $classes = $classes + [
            'bot' => ControllerBot::class,
            'storage' => RedisStorage::class
        ];

        $subClass = [
            'bot' => Bot::class,
            'storage' => StorageInterface::class
        ];

        foreach ($classes as $key => $class){

            if(array_has($subClass, $key)){
                // Check is needed

                if(is_subclass_of($class, $subClass[$key]) || $class == $subClass[$key]){
                    // Check is passed

                    if(interface_exists($subClass[$key])){
                        // Register under interface
                        $this->getContainer()->bind($subClass[$key], $class);
                    }else{
                        // Register under key
                        $this->getContainer()->bind($key, $class);
                    }

                }else{
                    // Check is failed
                    throw new InvalidArgumentException("Class " .$class . " needs to implement " .$subClass[$key]);
                }
            }else{

                // No check needed
                $this->getContainer()->bind($key, $class);
            }

        }

        if(is_a($classes['bot'], Bot::class)){
            $this->getContainer()->bind('bot', $classes['bot']);
        }
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

        if($this->hasContainer()){
            $this->bots[$name]->setContainer($this->getContainer());
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
        $repo = new MetaDataRepository($this->getContainer()->make(StorageInterface::class));
        return $this->getContainer()->make('bot', [$api, $config, $repo, null]);
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
