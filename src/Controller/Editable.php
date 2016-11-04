<?php

namespace Chassis\Controller;

use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Objects\Message;

trait Editable
{
    /**
     * Helper function to edit messages
     *
     * @param array $params Parameter for editMessageText method
     * @param Message $message
     * @param boolean $ignoreErrors Ignore "not modified" and "empty message text" errors
     * @return Message|bool
     *
     * @throws TelegramResponseException
     */
    function editMessage($params, Message $message = null, $ignoreErrors = false)
    {
        if ($message !== null) {
            $params['message_id'] = $message->getMessageId();
            $params['chat_id'] = $message->getChat()->getId();
        }

        // TODO: Try/Catch is just for logging
        try {
            return $this->getTelegram()->editMessageText($params);
        } catch (TelegramResponseException $e) {
            if ($e->getResponseData()['description'] == "Bad Request: message is not modified") {
                \Log::info("Inline Update Error (not modified): ", $e->getResponseData());
            } elseif ($e->getResponseData()['description'] == "Bad Request: Message text is empty") {
                \Log::info("Inline Update Error (empty): ", $e->getResponseData());
            } else {
                throw $e;
            }
            if (!$ignoreErrors) {
                throw $e;
            }
        }
    }
}
