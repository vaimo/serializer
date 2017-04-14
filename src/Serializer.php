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
use JMS\Serializer\ContextFactory\DefaultDeserializationContextFactory;
use JMS\Serializer\ContextFactory\DefaultSerializationContextFactory;
use JMS\Serializer\ContextFactory\DeserializationContextFactoryInterface;
use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Util\ArrayObject;
use Metadata\MetadataFactoryInterface;

/**
 * Serializer Implementation.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Serializer implements SerializerInterface, ArrayTransformerInterface
{
    private $factory;
    private $handlerRegistry;
    private $objectConstructor;
    private $dispatcher;
    private $typeParser;

    /** @var \PhpCollection\MapInterface */
    private $serializationVisitors;

    /** @var \PhpCollection\MapInterface */
    private $deserializationVisitors;

    private $serializationNavigator;
    private $deserializationNavigator;

    /**
     * @var SerializationContextFactoryInterface
     */
    private $serializationContextFactory;

    /**
     * @var DeserializationContextFactoryInterface
     */
    private $deserializationContextFactory;

    /**
     * Constructor.
     *
     * @param \Metadata\MetadataFactoryInterface $factory
     * @param Handler\HandlerRegistryInterface $handlerRegistry
     * @param Construction\ObjectConstructorInterface $objectConstructor
     * @param array $serializationVisitors of VisitorInterface
     * @param array $deserializationVisitors of VisitorInterface
     * @param EventDispatcherInterface|null $dispatcher
     * @param TypeParser|null $typeParser
     * @param ExpressionEvaluatorInterface|null $expressionEvaluator
     * @param SerializationContextFactoryInterface|null $serializationContextFactory
     * @param DeserializationContextFactoryInterface|null $deserializationContextFactory
     */
    public function __construct(
        MetadataFactoryInterface $factory,
        HandlerRegistryInterface $handlerRegistry,
        ObjectConstructorInterface $objectConstructor,
        array $serializationVisitors,
        array $deserializationVisitors,
        EventDispatcherInterface $dispatcher = null,
        TypeParser $typeParser = null,
        ExpressionEvaluatorInterface $expressionEvaluator = null,
        SerializationContextFactoryInterface $serializationContextFactory = null,
        DeserializationContextFactoryInterface $deserializationContextFactory = null
    )
    {
        $this->factory = $factory;
        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->typeParser = $typeParser ?: new TypeParser();
        $this->serializationVisitors = $serializationVisitors;
        $this->deserializationVisitors = $deserializationVisitors;

        $this->serializationNavigator = new SerializationGraphNavigator($this->factory, $this->handlerRegistry, $this->dispatcher, $expressionEvaluator);
        $this->deserializationNavigator = new DeserializationGraphNavigator($this->factory, $this->handlerRegistry, $this->dispatcher, $objectConstructor);

        $this->serializationContextFactory = $serializationContextFactory ?: new DefaultSerializationContextFactory();
        $this->deserializationContextFactory = $deserializationContextFactory ?: new DefaultDeserializationContextFactory();
    }

    public function serialize($data, $format, SerializationContext $context = null, $type = null)
    {
        if (null === $context) {
            $context = $this->serializationContextFactory->createSerializationContext();
        }

        if (!isset($this->serializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for serialization.', $format));
        }

        return call_user_func(function (SerializationVisitorInterface $visitor) use ($context, $data, $format, $type) {
            $type = $this->findInitialType($type, $context);

            if (!$visitor instanceof SerializationVisitorInterface) {
                $data = $visitor->prepare($data);
            }

            $result = $this->visit($this->serializationNavigator, $visitor, $context, $data, $format, $type);

            if ($visitor instanceof SerializationVisitorInterface) {
                return $visitor->getSerializationResult($result);
            } else {
                return $visitor->getResult();
            }
        }, $this->serializationVisitors[$format]);
    }

    private function findInitialType($type, SerializationContext $context)
    {
        if ($type !== null) {
            return $this->typeParser->parseAsDefinition($type);
        } elseif ($context->attributes->containsKey('initial_type')) {
            return $this->typeParser->parseAsDefinition($context->attributes->get('initial_type')->get());
        }
        return null;
    }

    public function deserialize($data, $type, $format, DeserializationContext $context = null)
    {
        if (null === $context) {
            $context = $this->deserializationContextFactory->createDeserializationContext();
        }

        if (!isset($this->deserializationVisitors[$format])) {
            throw new UnsupportedFormatException(sprintf('The format "%s" is not supported for deserialization.', $format));
        }

        return call_user_func(function (DeserializationVisitorInterface $visitor) use ($context, $data, $format, $type) {
                $preparedData = $visitor->prepare($data);
                return $this->visit($this->deserializationNavigator, $visitor, $context, $preparedData, $format, $this->typeParser->parseAsDefinition($type));
            }, $this->deserializationVisitors[$format]);
    }

    /**
     * {@InheritDoc}
     */
    public function toArray($data, SerializationContext $context = null, $type = null)
    {
        if (null === $context) {
            $context = $this->serializationContextFactory->createSerializationContext();
        }

        return call_user_func(function (JsonSerializationVisitor $visitor) use ($context, $data, $type) {

            $type = $this->findInitialType($type, $context);

            $result = $this->visit($this->serializationNavigator, $visitor, $context, $data, 'json', $type);
            $result = $this->removeInternalArrayObjects($result);

            if (!is_array($result)) {
                throw new RuntimeException(sprintf(
                    'The input data of type "%s" did not convert to an array, but got a result of type "%s".',
                    is_object($data) ? get_class($data) : gettype($data),
                    is_object($result) ? get_class($result) : gettype($result)
                ));
            }

            return $result;
        }, $this->serializationVisitors['json']);
    }

    /**
     * {@InheritDoc}
     */
    public function fromArray(array $data, $type, DeserializationContext $context = null)
    {
        if (null === $context) {
            $context = $this->deserializationContextFactory->createDeserializationContext();
        }

        return call_user_func(function (JsonDeserializationVisitor $visitor) use ($data, $type, $context) {
            return $this->visit($this->deserializationNavigator, $visitor, $context, $data, 'json', $this->typeParser->parseAsDefinition($type));
        }, $this->deserializationVisitors['json']);
    }

    private function visit(GraphNavigatorInterface $navigator, $visitor, Context $context, $data, $format, TypeDefinition $type = null)
    {
        $context->initialize(
            $format,
            $visitor,
            $navigator,
            $this->factory
        );

        if ($visitor instanceof SerializationVisitorInterface) {
            $visitor->initialize($navigator);
        } else {
            $visitor->setNavigator($navigator);
        }

        return $navigator->acceptData($data, $type, $context);
    }

    /**
     * @param $data
     * @return array
     */
    private function removeInternalArrayObjects($data)
    {
        if ($data instanceof ArrayObject) {
            $data = $data->getArrayCopy();
        }

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->removeInternalArrayObjects($v);
            }
        }

        return $data;
    }
}
