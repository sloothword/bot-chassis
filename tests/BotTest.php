<?php

namespace Chassis\Tests;

use Chassis\Bot\Bot;
use League\Event\Emitter;
use Prophecy\Argument;

class BotTest extends BaseTestCase
{

    public function testUpdateCheck()
    {
        $updateId = 17;

        $update = $this->factory->mockUpdate($updateId)->reveal();

        $apiProphecy = $this->factory->mockApi([$update]);

        // Test: the Event Emitter will be initialized
        $apiProphecy->setEventEmitter(Argument::type(Emitter::class))
            ->willReturn()
            ->shouldBeCalled();

        // Test: the update gets confirmed
        $apiProphecy->getUpdates(Argument::withEntry('offset', $updateId + 1), false)
            ->shouldBeCalled();

        // Do the work
        $bot = new Bot($apiProphecy->reveal(), []);
        $updates = $bot->checkForUpdates();

        // Test: the update gets returned
        $this->assertEquals($updates, [$update]);
    }
}
