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

use JMS\Serializer\Accessor\AccessorStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\Util\Writer;
use Symfony\Component\Yaml\Inline;

/**
 * Serialization Visitor for the YAML format.
 *
 * @see http://www.yaml.org/spec/
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class YamlSerializationVisitor extends AbstractVisitor implements SerializationVisitorInterface
{
    use SerializationLegacyTrait;
    public $writer;

    private $navigator;
    private $stack;
    private $metadataStack;
    private $currentMetadata;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy, AccessorStrategyInterface $accessorStrategy = null)
    {
        parent::__construct($namingStrategy, $accessorStrategy);
    }

    public function initialize(GraphNavigatorInterface $navigator):void
    {
        $this->navigator = $navigator;
        $this->stack = new \SplStack;
        $this->metadataStack = new \SplStack;
    }

    public function serializeNull(TypeDefinition $type, SerializationContext $context)
    {
        return 'null';
    }

    public function serializeString($data, TypeDefinition $type, SerializationContext $context)
    {
        return Inline::dump($data);
    }

    /**
     * @param array $data
     * @param TypeDefinition $type
     */
    public function serializeArray($data, TypeDefinition $type, SerializationContext $context)
    {
        $writer = new Writer();

        $isHash = $type->hasParam(1);

        $count = $writer->changeCount;
        $isList = $type->hasParam(0) && !$type->hasParam(1)
            || array_keys($data) === range(0, count($data) - 1);

        foreach ($data as $k => $v) {
            if (null === $v && $context->shouldSerializeNull() !== true) {
                continue;
            }

            if ($isList && !$isHash) {
                $writer->writeln('- ');
            } else {
                $writer->writeln(Inline::dump($k) . ': ');
            }

            $writer->indent();

            if (null !== $v = $this->navigator->acceptData($v, $this->findElementType($type), $context)) {
                $writer
                    ->rtrim(false)
                    ->writeln(' ' . $v);
            }

            $writer->outdent();
        }

        if ($count === $writer->changeCount && $type->hasParam(1)) {
            $writer
                ->rtrim(false)
                ->writeln('{}');
        } elseif (empty($data)) {
            $writer
                ->rtrim(false)
                ->writeln('[]');
        }

        return $writer->getContent();
    }

    public function serializeBoolean($data, TypeDefinition $type, SerializationContext $context)
    {
        return $data ? 'true' : 'false';
    }

    public function serializeFloat($data, TypeDefinition $type, SerializationContext $context)
    {
        return (string)$data;
    }

    public function serializeInteger($data, TypeDefinition $type, SerializationContext $context)
    {
        return (string)$data;
    }

    public function startSerializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, SerializationContext $context):void
    {
        $this->writer = new Writer();
    }

    public function serializeProperty(PropertyMetadata $metadata, $data, SerializationContext $context):void
    {
        $v = $this->accessor->getValue($data, $metadata);

        if (null === $v && $context->shouldSerializeNull() !== true) {
            return;
        }

        $name = $this->namingStrategy->translateName($metadata);

        if (!$metadata->inline) {
            $this->writer
                ->writeln(Inline::dump($name) . ': ')
                ->indent();
        }

        $this->setCurrentMetadata($metadata);

        $count = $this->writer->changeCount;

        if (null !== $v = $this->navigator->acceptData($v, $metadata->getTypeDefinition(), $context)) {
            $this->writer
                ->rtrim(false)
                ->writeln(' '.$v);
        } elseif ($count === $this->writer->changeCount && !$metadata->inline) {
            $this->writer->revert();
        }

        if (!$metadata->inline) {
            $this->writer->outdent();
        }
        $this->revertCurrentMetadata();
    }

    public function endSerializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, SerializationContext $context)
    {
        return $this->writer->getContent();
    }

    public function setCurrentMetadata(PropertyMetadata $metadata)
    {
        $this->metadataStack->push($this->currentMetadata);
        $this->currentMetadata = $metadata;
    }

    public function revertCurrentMetadata()
    {
        return $this->currentMetadata = $this->metadataStack->pop();
    }

    public function getSerializationResult($data)
    {
        return rtrim($data)."\n";
    }
    /**
     * @deprecated
     */
    public function getResult()
    {
        return $this->writer->getContent();
    }
}
