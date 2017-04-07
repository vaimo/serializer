<?php

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class ObjectWithIntArrayObjectListAndIntMap
{
    /** @Serializer\Type("ArrayObject<integer>") @Serializer\XmlList */
    public $list;

    /** @Serializer\Type("ArrayObject<string,integer>") @Serializer\XmlMap */
    public $map;

    public function __construct(array $list, array $map)
    {
        $this->list = $list;
        $this->map = $map;
    }
}
