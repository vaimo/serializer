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

use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Exception\ExpressionLanguageRequiredException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exclusion\ExpressionLanguageExclusionStrategy;
use JMS\Serializer\Expression\ExpressionEvaluatorInterface;
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
final class SerializationGraphNavigator extends GraphNavigator implements GraphNavigatorInterface
{
    /**
     * @var ExpressionLanguageExclusionStrategy
     */
    private $expressionExclusionStrategy;

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        HandlerRegistryInterface $handlerRegistry,
        EventDispatcherInterface $dispatcher,
        ExpressionEvaluatorInterface $expressionEvaluator = null
    )
    {
        parent::__construct($metadataFactory, $handlerRegistry, $dispatcher);
        if ($expressionEvaluator) {
            $this->expressionExclusionStrategy = new ExpressionLanguageExclusionStrategy($expressionEvaluator);
        }
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
        $visitor = $context->getVisitor();

        // If the type was not given, we infer the most specific type from the
        // input data in serialization mode.
        if (null === $type || $type->isUnknown()) {

            $typeName = gettype($data);
            if ('object' === $typeName) {
                $typeName = get_class($data);
            }

            $type = new TypeDefinition($typeName);
        }
        // If the data is null, we have to force the type to null regardless of the input in order to
        // guarantee correct handling of null values, and not have any internal auto-casting behavior.
        else if (null === $data) {
            $type = new TypeDefinition("NULL");
        }

        switch ($type->getName()) {
            case 'NULL':
                return $visitor->serializeNull($type, $context);

            case 'string':
                return $visitor->serializeString($data, $type, $context);

            case 'int':
            case 'integer':
                return $visitor->serializeInteger($data, $type, $context);

            case 'boolean':
                return $visitor->serializeBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->serializeFloat($data, $type, $context);

            case 'array':
                return $visitor->serializeArray($data, $type, $context);

            case 'resource':
                $msg = 'Resources are not supported in serialized data.';
                if (null !== $path = $context->getPath()) {
                    $msg .= ' Path: ' . $path;
                }

                throw new RuntimeException($msg);

            default:
                // TODO: The rest of this method needs some refactoring.

                if (null !== $data) {
                    if ($context->isVisiting($data)) {
                        return null;
                    }
                    $context->startVisiting($data);
                }

                // If we're serializing a polymorphic type, then we'll be interested in the
                // metadata for the actual type of the object, not the base class.
                if (class_exists($type->getName(), false) || interface_exists($type->getName(), false)) {
                    if (is_subclass_of($data, $type->getName(), false)) {
                        $type = new TypeDefinition(get_class($data));
                    }
                }

                // Trigger pre-serialization callbacks, and listeners if they exist.
                // Dispatch pre-serialization event before handling data to have ability change type in listener
                if ($this->dispatcher->hasListeners('serializer.pre_serialize', $type->getName(), $context->getFormat())) {
                    $this->dispatcher->dispatch('serializer.pre_serialize', $type->getName(), $context->getFormat(), $event = new PreSerializeEvent($context, $data, $type->getArray()));
                    $type = TypeDefinition::fromArray($event->getType());
                }

                // First, try whether a custom handler exists for the given type. This is done
                // before loading metadata because the type name might not be a class, but
                // could also simply be an artifical type.
                if (null !== $handler = $this->handlerRegistry->getHandler($context->getDirection(), $type->getName(), $context->getFormat())) {
                    $rs = call_user_func($handler, $visitor, $data, $type->getArray(), $context);
                    $context->stopVisiting($data);
                    return $rs;
                }

                $exclusionStrategy = $context->getExclusionStrategy();

                /** @var $metadata ClassMetadata */
                $metadata = $this->metadataFactory->getMetadataForClass($type->getName());

                if ($metadata->usingExpression && !$this->expressionExclusionStrategy) {
                    throw new ExpressionLanguageRequiredException("To use conditional exclude/expose in {$metadata->name} you must configure the expression language.");
                }

                if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    $context->stopVisiting($data);

                    return null;
                }

                $context->pushClassMetadata($metadata);

                foreach ($metadata->preSerializeMethods as $method) {
                    $method->invoke($data);
                }
                $object = $data;

                $visitor->startSerializingObject($metadata, $object, $type, $context);
                foreach ($metadata->propertyMetadata as $propertyMetadata) {
                    if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
                        continue;
                    }

                    if (null !== $this->expressionExclusionStrategy && $this->expressionExclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
                        continue;
                    }

                    $context->pushPropertyMetadata($propertyMetadata);
                    $visitor->serializeProperty($propertyMetadata, $data, $context);
                    $context->popPropertyMetadata();
                }
                $this->afterVisitingObject($metadata, $data, $type, $context);

                return $visitor->endSerializingObject($metadata, $data, $type, $context);
        }
    }

    private function afterVisitingObject(ClassMetadata $metadata, $object, TypeDefinition $type, Context $context)
    {
        $context->stopVisiting($object);
        $context->popClassMetadata();

        foreach ($metadata->postSerializeMethods as $method) {
            $method->invoke($object);
        }

        if ($this->dispatcher->hasListeners('serializer.post_serialize', $metadata->name, $context->getFormat())) {
            $this->dispatcher->dispatch('serializer.post_serialize', $metadata->name, $context->getFormat(), new ObjectEvent($context, $object, $type->getArray()));
        }
    }
}
