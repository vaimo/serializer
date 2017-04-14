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

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Construction\ObjectInstantiatorInterface;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\Exception\ExpressionLanguageRequiredException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use Metadata\MetadataFactoryInterface;

/**
 * Handles traversal along the object graph.
 *
 * This class handles traversal along the graph, and calls different methods
 * on visitors, or custom handlers to process its nodes.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class DeserializationGraphNavigator extends GraphNavigator implements GraphNavigatorInterface
{
    private $objectConstructor;

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        HandlerRegistryInterface $handlerRegistry,
        EventDispatcherInterface $dispatcher,
        ObjectConstructorInterface $objectConstructor
    )
    {
        parent::__construct($metadataFactory, $handlerRegistry, $dispatcher);
        $this->dispatcher = $dispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed $data the data depends on the direction, and type of visitor
     * @param null|TypeDefinition $type
     * @param Context $context
     * @return mixed the return value depends on the direction, and type of visitor
     */
    public function acceptData($data, TypeDefinition $type = null, Context $context)
    {
        /**
         * @var $visitor DeserializationVisitorInterface
         */
        $visitor = $context->getVisitor();

        // If the type was not given, we infer the most specific type from the
        // input data in serialization mode.
        if (null === $type || $type->isUnknown()) {
            throw new RuntimeException('The type must be given for all properties when deserializing.');

            $typeName = gettype($data);
            if ('object' === $typeName) {
                $typeName = get_class($data);
            }

            $type = new TypeDefinition($typeName);
        }

        switch ($type->getName()) {
            case 'NULL':
                return null;

            case 'string':
                return $visitor->deserializeString($data, $type, $context);

            case 'int':
            case 'integer':
                return $visitor->deserializeInteger($data, $type, $context);

            case 'boolean':
                return $visitor->deserializeBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->deserializeFloat($data, $type, $context);

            case 'array':
                return $visitor->deserializeArray($data, $type, $context);

            case 'resource':
                throw new RuntimeException('Resources are not supported in serialized data.');

            default:
                // TODO: The rest of this method needs some refactoring.

                $context->increaseDepth();


                // Trigger pre-serialization callbacks, and listeners if they exist.
                // Dispatch pre-serialization event before handling data to have ability change type in listener

                if (null !== $this->dispatcher && $this->dispatcher->hasListeners('serializer.pre_deserialize', $type->getName(), $context->getFormat())) {
                    $this->dispatcher->dispatch('serializer.pre_deserialize', $type->getName(), $context->getFormat(), $event = new PreDeserializeEvent($context, $data, $type));
                    $type = TypeDefinition::fromArray($event->getType());
                    $data = $event->getData();
                }

                // First, try whether a custom handler exists for the given type. This is done
                // before loading metadata because the type name might not be a class, but
                // could also simply be an artifical type.
                if (null !== $handler = $this->handlerRegistry->getHandler($context->getDirection(), $type->getName(), $context->getFormat())) {
                    $rs = call_user_func($handler, $visitor, $data, $type->getArray(), $context);
                    $context->decreaseDepth();
                    return $rs;
                }

                $exclusionStrategy = $context->getExclusionStrategy();

                /** @var $metadata ClassMetadata */
                $metadata = $this->metadataFactory->getMetadataForClass($type->getName());

                if ($metadata->usingExpression) {
                    throw new ExpressionLanguageRequiredException("To use conditional exclude/expose in {$metadata->name} you must configure the expression language.");
                }

                if (!empty($metadata->discriminatorMap) && $type->getName() === $metadata->discriminatorBaseClass) {
                    $metadata = $this->resolveMetadata($data, $metadata);
                }

                if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    $context->decreaseDepth();

                    return null;
                }

                $context->pushClassMetadata($metadata);

                if ($this->objectConstructor instanceof ObjectInstantiatorInterface) {
                    $object = $this->objectConstructor->instantiate($visitor, $metadata, $data, $type, $context);
                } else {
                    $object = $this->objectConstructor->construct($visitor, $metadata, $data, $type->getArray(), $context);
                }


                $visitor->startDeserializingObject($metadata, $object, $type, $context);
                foreach ($metadata->propertyMetadata as $propertyMetadata) {
                    if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
                        continue;
                    }

                    if ($propertyMetadata->readOnly) {
                        continue;
                    }

                    $context->pushPropertyMetadata($propertyMetadata);
                    $visitor->deserializeProperty($propertyMetadata, $data, $context);
                    $context->popPropertyMetadata();
                }

                $rs = $visitor->endDeserializingObject($metadata, $data, $type, $context);
                $this->afterDeserializingObject($metadata, $rs, $type, $context);

                return $rs;
        }
    }

    private function resolveMetadata($data, ClassMetadata $metadata)
    {
        switch (true) {
            case is_array($data) && isset($data[$metadata->discriminatorFieldName]):
                $typeValue = (string)$data[$metadata->discriminatorFieldName];
                break;

            // Check XML attribute for discriminatorFieldName
            case is_object($data) && $metadata->xmlDiscriminatorAttribute && isset($data[$metadata->discriminatorFieldName]):
                $typeValue = (string)$data[$metadata->discriminatorFieldName];
                break;

            case is_object($data) && isset($data->{$metadata->discriminatorFieldName}):
                $typeValue = (string)$data->{$metadata->discriminatorFieldName};
                break;

            default:
                throw new LogicException(sprintf(
                    'The discriminator field name "%s" for base-class "%s" was not found in input data.',
                    $metadata->discriminatorFieldName,
                    $metadata->name
                ));
        }

        if (!isset($metadata->discriminatorMap[$typeValue])) {
            throw new LogicException(sprintf(
                'The type value "%s" does not exist in the discriminator map of class "%s". Available types: %s',
                $typeValue,
                $metadata->name,
                implode(', ', array_keys($metadata->discriminatorMap))
            ));
        }

        return $this->metadataFactory->getMetadataForClass($metadata->discriminatorMap[$typeValue]);
    }

    private function afterDeserializingObject(ClassMetadata $metadata, $object, TypeDefinition $type, Context $context)
    {
        $context->decreaseDepth();
        $context->popClassMetadata();

        foreach ($metadata->postDeserializeMethods as $method) {
            $method->invoke($object);
        }

        if (null !== $this->dispatcher && $this->dispatcher->hasListeners('serializer.post_deserialize', $metadata->name, $context->getFormat())) {
            $this->dispatcher->dispatch('serializer.post_deserialize', $metadata->name, $context->getFormat(), new ObjectEvent($context, $object, $type->getArray()));
        }
    }
}
