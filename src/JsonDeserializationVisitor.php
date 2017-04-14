<?php

/*
 * Copyright 2016 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * JSON Deserialization Visitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class JsonDeserializationVisitor extends AbstractVisitor implements DeserializationVisitorInterface
{
    use DeserializationLegacyTrait;

    private $navigator;
    private $objectStack;
    private $currentObject;

    public function initialize(GraphNavigatorInterface $navigator):void
    {
        $this->navigator = $navigator;
        $this->objectStack = new \SplStack;
    }

    public function deserializeNull($data, TypeDefinition $type, DeserializationContext $context):void
    {
    }

    public function deserializeString($data, TypeDefinition $type, DeserializationContext $context):string
    {
        return (string)$data;
    }

    public function deserializeBoolean($data, TypeDefinition $type, DeserializationContext $context):bool
    {
        return (Boolean)$data;
    }

    public function deserializeInteger($data, TypeDefinition $type, DeserializationContext $context):int
    {
        return (int)$data;
    }

    public function deserializeFloat($data, TypeDefinition $type, DeserializationContext $context):float
    {
        return (double)$data;
    }

    public function deserializeArray($data, TypeDefinition $type, DeserializationContext $context)
    {
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Expected array, but got %s: %s', gettype($data), json_encode($data)));
        }

        // If no further parameters were given, keys/values are just passed as is.
        if (!$type->getParams()) {
            return $data;
        }

        switch (count($type->getParams())) {
            case 1: // Array is a list.
                $listType = $type->getParam(0);

                $result = array();

                foreach ($data as $v) {
                    $result[] = $this->navigator->accept($v, $listType->getArray(), $context);
                }

                return $result;

            case 2: // Array is a map.
                $entryType = $type->getParam(1);

                $result = array();

                foreach ($data as $k => $v) {
                    $result[$k] = $this->navigator->accept($v, $entryType->getArray(), $context);
                }

                return $result;

            default:
                throw new RuntimeException(sprintf('Array type cannot have more than 2 parameters, but got %s.', json_encode($type['params'])));
        }
    }

    public function startDeserializingObject(ClassMetadata $metadata, $object, TypeDefinition $type, DeserializationContext $context):void
    {
        $this->setCurrentObject($object);
    }

    public function deserializeProperty(PropertyMetadata $metadata, $data, DeserializationContext $context):void
    {
        $name = $this->namingStrategy->translateName($metadata);

        if (null === $data) {
            return;
        }

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Invalid data "%s"(%s), expected "%s".', $data, $metadata->type['name'], $metadata->reflection->class));
        }

        if (!array_key_exists($name, $data)) {
            return;
        }

        if (!$metadata->type) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->reflection->class, $metadata->name));
        }

        $v = $data[$name] !== null ? $this->navigator->accept($data[$name], $metadata->type, $context) : null;

        $this->accessor->setValue($this->currentObject, $v, $metadata);

    }

    public function endDeserializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, DeserializationContext $context)
    {
        $obj = $this->currentObject;
        $this->revertCurrentObject();

        return $obj;
    }

    public function setCurrentObject($object)
    {
        $this->objectStack->push($this->currentObject);
        $this->currentObject = $object;
    }

    public function getCurrentObject()
    {
        return $this->currentObject;
    }

    public function revertCurrentObject()
    {
        return $this->currentObject = $this->objectStack->pop();
    }

    public function prepareData($str)
    {
        $decoded = json_decode($str, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;

            case JSON_ERROR_DEPTH:
                throw new RuntimeException('Could not decode JSON, maximum stack depth exceeded.');

            case JSON_ERROR_STATE_MISMATCH:
                throw new RuntimeException('Could not decode JSON, underflow or the nodes mismatch.');

            case JSON_ERROR_CTRL_CHAR:
                throw new RuntimeException('Could not decode JSON, unexpected control character found.');

            case JSON_ERROR_SYNTAX:
                throw new RuntimeException('Could not decode JSON, syntax error - malformed JSON.');

            case JSON_ERROR_UTF8:
                throw new RuntimeException('Could not decode JSON, malformed UTF-8 characters (incorrectly encoded?)');

            default:
                throw new RuntimeException('Could not decode JSON.');
        }
    }
}
