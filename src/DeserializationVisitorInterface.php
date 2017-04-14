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

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Util\ArrayObject;

/**
 * Interface for deserializing visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface DeserializationVisitorInterface
{
    /**
     * Allows to convert the input data to a different representation
     * before the actual deserialization process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepareData($data);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return void
     */
    public function deserializeNull($data, TypeDefinition $type, DeserializationContext $context):void;

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return string
     */
    public function deserializeString($data, TypeDefinition $type, DeserializationContext $context):string;

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return boolean
     */
    public function deserializeBoolean($data, TypeDefinition $type, DeserializationContext $context):bool;

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return float
     */
    public function deserializeFloat($data, TypeDefinition $type, DeserializationContext $context):float;

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return integer
     */
    public function deserializeInteger($data, TypeDefinition $type, DeserializationContext $context):int;

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return array|ArrayObject
     */
    public function deserializeArray($data, TypeDefinition $type, DeserializationContext $context);

    /**
     * Called before the properties of the object are being deserializeed.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return void
     */
    public function startDeserializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, DeserializationContext $context):void;

    /**
     * @param PropertyMetadata $metadata
     * @param mixed $data
     * @param DeserializationContext $context
     *
     * @return void
     */
    public function deserializeProperty(PropertyMetadata $metadata, $data, DeserializationContext $context):void;

    /**
     * Called after all properties of the object have been deserializeed.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param TypeDefinition $type
     * @param DeserializationContext $context
     *
     * @return object
     */
    public function endDeserializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, DeserializationContext $context);

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigatorInterface $navigator
     *
     * @return void
     */
    public function initialize(GraphNavigatorInterface $navigator):void;
}
