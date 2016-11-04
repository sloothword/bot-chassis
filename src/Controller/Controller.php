<?php

namespace Chassis\Controller;

use Chassis\Bot\ControllerBot;
use Chassis\Helper\Pagination;
use Chassis\MetaData\ConversationData;
use Chassis\MetaData\MessageData;
use Chassis\MetaData\MetaData;
use Chassis\MetaData\MetaDataRepository;

use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

use View;

/**
 * Controllers encapsulate the handling logic for a specific Update type in a single class
 */
class Controller
{

    use \Telegram\Bot\Answers\Answerable;
    use \Chassis\Controller\Editable;

    /**
     * @var ControllerBot The associated Bot
     */
    var $bot;

    /**
     *
     * @var MetaDataRepository
     */
    private $metaDataRepository;

    /**
     * @return Bot
     */
    public function getBot()
    {
        return $this->bot;
    }

    public function __construct(ControllerBot $bot, Update $update, MetaDataRepository $metaDataRepository)
    {
        $this->update = $update;
        $this->bot = $bot;
        $this->metaDataRepository = $metaDataRepository;

        /** @TODO: Fix in Answerable (getTelegram calls $bot->getT internally) */
        $this->telegram = $bot->getTelegram();
    }

    /**
     *
     * @param Update|Message|User|Chat $object
     * @return MetaData
     */
    public function getMetaData($object)
    {
        return $this->metaDataRepository->load($object);
    }

    /**
     * Get ConversationData associated with current user and chat
     *
     * @return ConversationData
     */
    public function getConversationData()
    {
        return $this->getMetaData($this->getUpdate());
    }

    /**
     * Get associated MessageData
     *
     * @return MessageData
     */
    public function getMessageData($message)
    {
        return $this->getMetaData($message);
    }

    /**
     * Helper function to create a reply Keyboard from a Collection
     * @TODO: WIP, needs rewrite or move
     *
     * @param array $buttons
     * @param boolean $inline
     * @param int $cols Number of buttons per row
     * @return Keyboard
     */
    protected function createReplyKeyboard($buttons, $inline = false, $cols = null)
    {

        $count = count($buttons);
        if ($cols == null) {
            $cols = 3;
        }

        $rows = [];
        $row = [];
        foreach ($buttons as $button) {

            if ($inline) {
                $row[] = Keyboard::inlineButton(['text' => $button, 'callback_data' => $button]);
            } else {
                $row[] = Keyboard::button(['text' => $button]);
            }

            if (count($row) == $cols) {
                $rows[] = $row;
                $row = [];
            }
        }
        if (count($row) > 0) {
            $rows[] = $row;
        }

        $property = 'keyboard';
        if ($inline) {
            $property = 'inline_keyboard';
        }

        $k = Keyboard::make([$property => $rows]);
        $k->setResizeKeyboard(true);
        $k->setOneTimeKeyboard(true);
        return $k;
    }

    /**
     * Ask the user some basic questions one after the other.
     *
     * @param array $questions
     * Each entry defines one question, the answer is stored under the key.
     * The first field of the question is the text, the optional second defines the method returning answer suggestions.
     *
     * Example:
     * ['key1' => ['Question to Ask for Data1', 'getData1Suggestions']
     *
     * @return boolean returns whether questionaire is complete
     */
    public function completeQuestionnaire($questions)
    {
        /** @TODO: Add option to abort */
//        if($this->shallAbort()){
//            $this->abortConversation();
//            return;
//        }
        // Check what was asked last and save answer
        $userData = $this->getConversationData();
        if ($userData->has('asked')) {
            $userData[$userData['asked']] = $this->getText();
        }

        // Get first unasked question
        foreach ($questions as $key => $requiredData) {
            if (!$userData->has($key)) {
                $buttons = null;
                if (count($requiredData) > 1) {
                    $buttons = $this->{$requiredData[1]}($key);
                }
                $userData['asked'] = $key;
                $this->replyWithMessage(
                    $this->createReply(null, $requiredData[0], $buttons)
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Helper function to generate parameter array for the replyWithMessage and editMessage functions
     *
     * Function adds 'text', 'parse_mode' and 'reply_markup' properties to $param.
     *
     * @param string $view name of view to render for the text
     * @param array $data data for view or message text (if $view is null)
     * @param array|Keyboard|null $buttons Buttons for reply Keyboard. null to hide Keyboard
     * @param array $params
     * @return array
     */
    public function createReply($view = null, $data = null, $buttons = null, $params = [])
    {

        // If view set --> render view
        if ($view !== null) {
            $params['text'] = $this->renderView($view, $data);
            $params['parse_mode'] = 'markdown';
        } elseif ($data != null) {
            $params['text'] = $data;
        }

        if ($buttons === null) {
            $keyboard = Keyboard::hide();
        } elseif ($buttons instanceof Keyboard) {
            // Do nothing
            $keyboard = $buttons;
        } elseif (is_array($buttons) or $buttons instanceof \ArrayAccess) {
            if (isset($buttons['buttons'])) {
                $inline = isset($buttons['inline']) ? $buttons['inline'] : false;
                $cols = isset($buttons['cols']) ? $buttons['cols'] : null;
                $keyboard = $this->createReplyKeyboard($buttons['buttons'], $inline, $cols);
            } else {
                $keyboard = $this->createReplyKeyboard($buttons);
            }
        }
        $params['reply_markup'] = $keyboard;

        return $params;
    }

    /**
     * Render view and remove whitespace
     *
     * @param string $view
     * @param string $data
     * @return string
     */
    public function renderView($view, $data = [])
    {
        return str_replace("    ", "", View::make($view, $data)->render());
    }

    /**
     * Pass handling of update to a different controller
     *
     * @param string $controller class of controller
     * @param string $method
     * @param null|Update|string $update Update or message text
     */
    public function execute($controller, $method, $update = null)
    {
        if ($update === null) {
            $update = $this->getUpdate();
        } else if ($update instanceof Update) {

        } else {
            $update = new Update(['message' => ['text' => $update]]);
        }

        $this->getBot()->getControllerBus()->callController($controller, $method, $update, $this->metaDataRepository);
    }

    /**
     * Helper function to integrate pagination of messages
     *
     * @param int $perPage
     * @param int $count
     * @return Pagination
     */
    public function getPagination($perPage = 5, $count = -1)
    {
        $metaData = $this->getMessageData(ControllerBot::getMessage($this->getUpdate()));

        $page = $metaData->has('page') ? $metaData['page'] : 0;
        $perPage = $metaData->has('perPage') ? $metaData['perPage'] : $perPage;

        return new Pagination($page, $perPage, $count);
    }
}
