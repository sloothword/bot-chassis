<?php

namespace Chassis\Controller;

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
            'text' => $this->getText()
        ]);
    }

    /**
     * Echo text contained in Update twice
     */
    public function twice()
    {
        $this->replyWithMessage([
            'text' => $this->getText() . $this->getText()
        ]);
    }

    /**
     * Save text to echo after next Update
     */
    public function delayed()
    {
        $conversation = $this->getConversationData();
        $conversation['text'] = $this->getText();
        $conversation->nextConversationStep(self::class, 'delayedEcho');
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
