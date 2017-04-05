<?php

namespace JMS\Serializer\Tests\Handler;

use JMS\Serializer\SerializerBuilder;

class PropelCollectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  $serializer \JMS\Serializer\Serializer */
    private $serializer;

    public function setUp()
    {
        $this->serializer = SerializerBuilder::create()
            ->addDefaultHandlers() //load PropelCollectionHandler
            ->build();
    }
}

class TestSubject
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
