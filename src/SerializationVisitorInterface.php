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

/**
 * Interface for serializing visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SerializationVisitorInterface
{
    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual serialization/deserialization process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data);

    /**
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeNull(TypeDefinition $type, SerializationContext $context);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeString($data, TypeDefinition $type, SerializationContext $context);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeBoolean($data, TypeDefinition $type, SerializationContext $context);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeDouble($data, TypeDefinition $type, SerializationContext $context);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeInteger($data, TypeDefinition $type, SerializationContext $context);

    /**
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function serializeArray($data, TypeDefinition $type, SerializationContext $context);

    /**
     * Called before the properties of the object are being serializeed.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return void
     */
    public function startSerializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, SerializationContext $context):void;

    /**
     * @param PropertyMetadata $metadata
     * @param mixed $data
     * @param SerializationContext $context
     *
     * @return void
     */
    public function serializeProperty(PropertyMetadata $metadata, $data, SerializationContext $context):void;

    /**
     * Called after all properties of the object have been serialized.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param TypeDefinition $type
     * @param SerializationContext $context
     *
     * @return mixed
     */
    public function endSerializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, SerializationContext $context);

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigatorInterface $navigator
     *
     * @return void
     */
    public function initialize(GraphNavigatorInterface $navigator):void;

    /**
     * @param mixed $data
     * @return mixed
     */
    public function getSerializationResult($data);
}
