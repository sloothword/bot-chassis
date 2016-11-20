<?php

namespace Chassis\Bot;

use Chassis\Bot\Bot;
use Chassis\Controller\ControllerBus;
use Chassis\MetaData\MetaDataRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Uses Controllers to handle incoming Updates
 */
class ControllerBot extends Bot
{

    /** @var ControllerBus */
    protected $controllerBus = null;

    /** @var MetaDataRepository */
    protected $metaDataRepository;

    /**
     *
     * @param Api $api
     * @param array $config
     * @param MetaDataRepository $repo
     * @param ControllerBus $bus
     */
    public function __construct(Api $api, array $config, MetaDataRepository $repo, ControllerBus $bus = null)
    {
        if($bus !== null){
            $this->controllerBus = $bus;
        }else{
            $this->controllerBus = new ControllerBus($this);
        }

        parent::__construct($api, $config);


        $this->metaDataRepository = $repo;

    }

    /*
     * Create and register configured controllers
     *
     * @param array $config
     */
    protected function readConfig(array $config)
    {
        // Register Commands
        $this->controllerBus->addControllers($config['controllers']);
    }

    /**
     * Returns SDK's Command Bus.
     *
     * @TODO: should probably be private
     *
     * @return ControllerBus
     */
    public function getControllerBus()
    {
        return $this->controllerBus;
    }

    /**
     * Check update object for a command and process.
     *
     * @param Update $update
     */
    public function processUpdate(Update $update)
    {
        if ($update->has('callback_query')) {
            // Read MessageData
            $metaData = $this->metaDataRepository->load($this->getMessage($update));
        } else {
            // Read ConversationData
            $metaData = $this->metaDataRepository->load($update);
        }

        // Check for forced Controller
        if ($metaData->has('controller')) {
            $this->getControllerBus()->callController(
                $metaData['controller']['name'], $metaData['controller']['method'], $update, $this->metaDataRepository
            );
        } else {
            // No user data -> route Controller like normal
            $this->getControllerBus()->handler($update, $this->metaDataRepository);
        }

        // After the controller finished, save all MetaData
        $this->metaDataRepository->saveAll();
    }

    /* @TODO SDK: put into SDK */
    static function getMessage($update)
    {
        switch ($update->detectType()) {
            case 'message':
                $message = $update->getMessage();
                break;
            case 'callback_query':
                $message = $update->getCallbackQuery()->getMessage();
                break;
        }
        return $message;
    }
}
