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
 * Interface for visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface DeserializationVisitorInterface
{
    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual deserialization process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return null
     */
    public function visitNull($data, array $type, Context $context);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return string
     */
    public function visitString($data, array $type, Context $context);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return boolean
     */
    public function visitBoolean($data, array $type, Context $context);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return double
     */
    public function visitDouble($data, array $type, Context $context);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return integer
     */
    public function visitInteger($data, array $type, Context $context);

    /**
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return array|ArrayObject
     */
    public function visitArray($data, array $type, Context $context);

    /**
     * Called before the properties of the object are being visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return void
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context);

    /**
     * @param PropertyMetadata $metadata
     * @param mixed $data
     * @param DeserializationContext $context
     *
     * @return void
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context);

    /**
     * Called after all properties of the object have been visited.
     *
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type
     * @param DeserializationContext $context
     *
     * @return ArrayObject
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context);

    /**
     * Called before serialization/deserialization starts.
     *
     * @param GraphNavigatorInterface $navigator
     *
     * @return void
     */
    public function setNavigator(GraphNavigatorInterface $navigator);
}
