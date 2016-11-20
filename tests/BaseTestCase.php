<?php

namespace Chassis\Tests;

use Chassis\Tests\Mocks\MockFactory;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{

    /** @var MockFactory */
    public $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new MockFactory($this);
    }

    public function prophesize($classOrInterface = null)
    {
        return parent::prophesize($classOrInterface);
    }

}