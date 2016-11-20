<?php

namespace Chassis\Tests\Mocks;

use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\ChosenInlineResult;
use Telegram\Bot\Objects\EditedMessage;
use Telegram\Bot\Objects\InlineQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

/**
 * Class Update.
 *
 *
 * @method int                  getUpdateId()
 * @method Message              getMessage()
 * @method EditedMessage        getEditedMessage()
 * @method InlineQuery          getInlineQuery()
 * @method ChosenInlineResult   getChosenInlineResult()
 * @method CallbackQuery        getCallbackQuery()
 */
class UpdateMock extends Update{

}
