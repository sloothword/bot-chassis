<?php

namespace Chassis\Tests;

use Chassis\Bot\ControllerBot;
use Prophecy\Argument;
use Telegram\Bot\Objects\Update;

class ControllerBotTest extends BaseTestCase
{

    public function testConfiguration(){

        $api = $this->factory->mockApi();
        $config = $this->factory->mockConfig(true);
        $repo = $this->factory->mockMetaDataRepository();
        $bus = $this->factory->mockControllerBus();

        // check if controller get added to Bus
        $bus->addControllers($config['controllers'])
            ->willReturn()
            ->shouldBeCalled();

        new ControllerBot(
            $api->reveal(),
            $config,
            $repo->reveal(),
            $bus->reveal()
        );
    }


    public function processUpdateVariationsProvider(){
        return [
            [true, true],
            [false, true],
            [true, false],
            [false, false]
        ];
    }

    /**
     * @dataProvider processUpdateVariationsProvider
     */
    public function testProcessUpdate($callback, $forced){
        // Repeat:
        // - once for callback_query and once for normal update
        // - once for set metadata and once for no controller set
        // Check:
        // - correct ->handler or ->callController on Bus gets called
        // - metaData gets saved afterwards


        // Mock Update
        $update = $this->factory->mockUpdate();

        if($callback){
            $update->has('callback_query')
                ->willReturn(true);

            // Hacky, as it really should be a callback_query
            $update->detectType()
                ->willReturn('message');


        }else{
            $update->has('callback_query')
                ->willReturn(false);
        }

        // TODO: real message class
        $update->getMessage()
            ->willReturn('msg');


        $updateMock = $update->reveal();

        // Mock MetaData
        $repo = $this->factory->mockMetaDataRepository();

        if($forced){
            $metaData = $this->factory->mockMetaData(['controller' => ['name' => 'CONTROLLERNAME', 'method' => 'CONTROLLERMETHOD']], []);
        }else{
            $metaData = $this->factory->mockMetaData([], ['controller']);
        }

        // Test: MetaData gets saved
        $repo->saveAll()
            ->willReturn()
            ->shouldBeCalled();

        if($callback){
            $repo->load('msg')
                ->willReturn($metaData->reveal());
        }else{
            $repo->load($updateMock)
                ->willReturn($metaData->reveal());
        }


        // Mock ControllerBus
        $bus = $this->factory->mockControllerBus();

        // Test: correct handler is called
        if($forced){
            $bus->callController('CONTROLLERNAME', 'CONTROLLERMETHOD', $updateMock, $repo)
                ->willReturn()
                ->shouldBeCalled();
        }else{
            $bus->handler($updateMock, $repo->reveal())
                ->willReturn()
                ->shouldBeCalled();
        }
        $bus->addControllers(Argument::any())
            ->willReturn();

        // Create Bot
        $api = $this->factory->mockApi();
        $config = $this->factory->mockConfig(true);

        $bot = new ControllerBot(
            $api->reveal(),
            $config,
            $repo->reveal(),
            $bus->reveal()
        );

        $bot->processUpdate($updateMock);
    }

}
