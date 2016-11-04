<?php

namespace Chassis\Controller;

use Telegram\Bot\Objects\Update;
use Chassis\Bot\Bot;

/**
 * Example Controller
 */
class EchoController extends Controller
{

    /**
     * Echo text contained in Update once
     */
    public function once()
    {
        $this->replyWithMessage([
            'text' => $this->getUpdate()->getMessage()->getText()
        ]);
    }

    /**
     * Echo text contained in Update twice
     */
    public function twice()
    {
        $this->replyWithMessage([
            'text' => $this->getUpdate()->getMessage()->getText() . $this->getUpdate()->getMessage()->getText()
        ]);
    }

    /**
     * Save text to echo after next Update
     */
    public function delayed()
    {
        $this->getConversationData()['text'] = $this->getUpdate()->getMessage()->getText();
        $this->setNextController(self::class, 'delayedEcho');
    }

    /**
     * Reply with saved text earlier
     */
    public function delayedEcho()
    {
        $this->delayed();
        $this->replyWithMessage([
            'text' => $this->getConversationData()['text']
        ]);
    }
}
