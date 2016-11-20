<?php

namespace Chassis\Tests\Mocks;

use Chassis\Controller\Bubbling;
use Chassis\Controller\ControllerBus;
use Chassis\Controller\EchoController;
use Chassis\MetaData\MetaData;
use Chassis\MetaData\MetaDataRepository;
use Chassis\Tests\BaseTestCase;
use Prophecy\Argument;
use Telegram\Bot\Api;

class MockFactory{
    public function __construct(BaseTestCase $testCase){
        // TODO: we just want the contained prophet instance...
        $this->testCase = $testCase;
    }

    private function prophesize($class = null){
        return $this->testCase->prophesize($class);
    }

    /** @var BaseTestCase */
    var $testCase;


    var $testConfig = [
        'bots' => [
            'testbot' => [
                'username'  => 'BOT-USERNAME',
                'token' => 'BOT-TOKEN',
                'controllers' => [
                    ['text', EchoController::class, 'once', Bubbling::NONE],
                    ['/double', EchoController::class, 'twice', Bubbling::AFTER],
                    ['/delayed', EchoController::class, 'delayed', Bubbling::AFTER]
                ],
            ],
        ],

        'telegram' => [
            'TELEGRAM_KEY' => 'TELEGRAM_CONFIG'
        ]
    ];

    public function mockConfig($onlyBot = false){
        if($onlyBot){
            return $this->testConfig['bots']['testbot'];
        }else{
            return $this->testConfig;
        }
    }

    public function mockApi(array $updates = []){
        $api = $this->prophesize(Api::class);

        if(count($updates) > 0){
            $api->getUpdates(Argument::any())->willReturn($updates);
            $api->getWebhookUpdate(Argument::any())->willReturn($updates[0]);
        }

        return $api;
    }

    public function mockUpdate($id = 1){
        $update = $this->prophesize(UpdateMock::class);
        $update->getUpdateId()->willReturn($id);
        return $update;
    }

    public function mockMetaDataRepository(){
        return $this->prophesize(MetaDataRepository::class);
    }

    public function mockMetaData($has = [], $hasNot = []){
        $metaData = $this->prophesize(MetaData::class);

        foreach ($has as $key => $value) {
            $metaData->has(Argument::exact($key))->willReturn(true);
            $metaData->offsetGet(Argument::exact($key))->willReturn($value);
        }
        foreach ($hasNot as $key) {
            $metaData->has(Argument::exact($key))->willReturn(false);
        }

        return $metaData;
    }

    public function mockControllerBus(){
        return $this->prophesize(ControllerBus::class);
    }
//
//    public function mockCallback(){
//
//        $mock = $this->testCase->prophesize();
//
//        $callback = function (...$a) use (&$mock) {
//            $mock->invoke(...$a);
//        };
//
//        return [$callback, $mock];
//    }
}