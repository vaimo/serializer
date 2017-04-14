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

use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exception\XmlErrorException;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class XmlDeserializationVisitor extends AbstractVisitor implements DeserializationVisitorInterface
{
    use DeserializationLegacyTrait;

    private $objectStack;
    private $metadataStack;
    private $objectMetadataStack;
    private $currentObject;
    private $currentMetadata;
    private $navigator;
    private $disableExternalEntities = true;
    private $doctypeWhitelist = array();

    public function enableExternalEntities()
    {
        $this->disableExternalEntities = false;
    }

    public function initialize(GraphNavigatorInterface $navigator):void
    {
        $this->navigator = $navigator;
        $this->objectStack = new \SplStack;
        $this->metadataStack = new \SplStack;
        $this->objectMetadataStack = new \SplStack;
    }

    public function prepareData($data)
    {
        $data = $this->emptyStringToSpaceCharacter($data);

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $previousEntityLoaderState = libxml_disable_entity_loader($this->disableExternalEntities);

        if (false !== stripos($data, '<!doctype')) {
            $internalSubset = $this->getDomDocumentTypeEntitySubset($data);
            if (!in_array($internalSubset, $this->doctypeWhitelist, true)) {
                throw new InvalidArgumentException(sprintf(
                    'The document type "%s" is not allowed. If it is safe, you may add it to the whitelist configuration.',
                    $internalSubset
                ));
            }
        }

        $doc = simplexml_load_string($data);

        libxml_use_internal_errors($previous);
        libxml_disable_entity_loader($previousEntityLoaderState);

        if (false === $doc) {
            throw new XmlErrorException(libxml_get_last_error());
        }

        return $doc;
    }

    private function emptyStringToSpaceCharacter($data)
    {
        return $data === '' ? ' ' : $data;
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
        $data = (string)$data;

        if ('true' === $data || '1' === $data) {
            $data = true;
        } elseif ('false' === $data || '0' === $data) {
            $data = false;
        } else {
            throw new RuntimeException(sprintf('Could not convert data to boolean. Expected "true", "false", "1" or "0", but got %s.', json_encode($data)));
        }

        return $data;
    }

    public function deserializeInteger($data, TypeDefinition $type, DeserializationContext $context):int
    {
        return (integer)$data;
    }

    public function deserializeFloat($data, TypeDefinition $type, DeserializationContext $context):float
    {
        return (double)$data;
    }

    public function deserializeArray($data, TypeDefinition $type, DeserializationContext $context)
    {
        $entryName = null !== $this->currentMetadata && $this->currentMetadata->xmlEntryName ? $this->currentMetadata->xmlEntryName : 'entry';
        $namespace = null !== $this->currentMetadata && $this->currentMetadata->xmlEntryNamespace ? $this->currentMetadata->xmlEntryNamespace : null;

        if ($namespace === null && $this->objectMetadataStack->count()) {
            $classMetadata = $this->objectMetadataStack->top();
            $namespace = isset($classMetadata->xmlNamespaces['']) ? $classMetadata->xmlNamespaces[''] : $namespace;
        }

        if (null !== $namespace) {
            $prefix = uniqid('ns-');
            $data->registerXPathNamespace($prefix, $namespace);
            $nodes = $data->xpath("$prefix:$entryName");
        } else {
            $nodes = $data->xpath($entryName);
        }

        if (!count($nodes)) {
            return array();
        }

        switch (count($type->getParams())) {
            case 0:
                throw new RuntimeException(sprintf('The array type must be specified either as "array<T>", or "array<K,V>".'));

            case 1:
                $result = array();
                foreach ($nodes as $v) {
                    $result[] = $this->navigator->acceptData($v, $type->getParam(0), $context);
                }

                return $result;

            case 2:
                if (null === $this->currentMetadata) {
                    throw new RuntimeException('Maps are not supported on top-level without metadata.');
                }

                $entryType = $type->getParam(1);
                $result = array();

                $nodes = $data->children($namespace)->$entryName;
                foreach ($nodes as $v) {
                    $attrs = $v->attributes();
                    if (!isset($attrs[$this->currentMetadata->xmlKeyAttribute])) {
                        throw new RuntimeException(sprintf('The key attribute "%s" must be set for each entry of the map.', $this->currentMetadata->xmlKeyAttribute));
                    }

                    $result[(string)$attrs[$this->currentMetadata->xmlKeyAttribute]] = $this->navigator->acceptData($v, $entryType, $context);
                }

                return $result;

            default:
                throw new LogicException(sprintf('The array type does not support more than 2 parameters, but got %s.', json_encode($type['params'])));
        }
    }

    public function startDeserializingObject(ClassMetadata $metadata, $object, TypeDefinition $type, DeserializationContext $context):void
    {
        $this->setCurrentObject($object);
        $this->objectMetadataStack->push($metadata);
    }

    public function deserializeProperty(PropertyMetadata $metadata, $data, DeserializationContext $context):void
    {
        $name = $this->namingStrategy->translateName($metadata);

        if (!$metadata->type) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->reflection->class, $metadata->name));
        }

        if ($metadata->xmlAttribute) {

            $attributes = $data->attributes($metadata->xmlNamespace);
            if (isset($attributes[$name])) {
                $v = $this->navigator->acceptData($attributes[$name], TypeDefinition::fromArray($metadata->type), $context);
                $this->accessor->setValue($this->currentObject, $v, $metadata);
            }

            return;
        }

        if ($metadata->xmlValue) {
            $v = $this->navigator->acceptData($data, TypeDefinition::fromArray($metadata->type), $context);
            $this->accessor->setValue($this->currentObject, $v, $metadata);

            return;
        }

        if ($metadata->xmlCollection) {
            $enclosingElem = $data;
            if (!$metadata->xmlCollectionInline) {
                $enclosingElem = $data->children($metadata->xmlNamespace)->$name;
            }

            $this->setCurrentMetadata($metadata);
            $v = $this->navigator->acceptData($enclosingElem, TypeDefinition::fromArray($metadata->type), $context);
            $this->revertCurrentMetadata();
            $this->accessor->setValue($this->currentObject, $v, $metadata);

            return;
        }

        if ($metadata->xmlNamespace) {
            $node = $data->children($metadata->xmlNamespace)->$name;
            if (!$node->count()) {
                return;
            }
        } else {

            $namespaces = $data->getDocNamespaces();

            if (isset($namespaces[''])) {
                $prefix = uniqid('ns-');
                $data->registerXPathNamespace($prefix, $namespaces['']);
                $nodes = $data->xpath('./' . $prefix . ':' . $name);
            } else {
                $nodes = $data->xpath('./' . $name);
            }
            if (empty($nodes)) {
                return;
            }
            $node = reset($nodes);
        }

        $v = $this->navigator->acceptData($node, TypeDefinition::fromArray($metadata->type), $context);

        $this->accessor->setValue($this->currentObject, $v, $metadata);
    }

    public function endDeserializingObject(ClassMetadata $metadata, $data, TypeDefinition $type, DeserializationContext $context)
    {
        $rs = $this->currentObject;
        $this->objectMetadataStack->pop();
        $this->revertCurrentObject();

        return $rs;
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

    public function setCurrentMetadata(PropertyMetadata $metadata)
    {
        $this->metadataStack->push($this->currentMetadata);
        $this->currentMetadata = $metadata;
    }

    public function getCurrentMetadata()
    {
        return $this->currentMetadata;
    }

    public function revertCurrentMetadata()
    {
        return $this->currentMetadata = $this->metadataStack->pop();
    }

    /**
     * @deprecated
     */
    public function getResult()
    {
        throw new RuntimeException(__METHOD__ . " has been deprecated for deserialization deserializeors");
    }

    /**
     * @param array <string> $doctypeWhitelist
     */
    public function setDoctypeWhitelist(array $doctypeWhitelist)
    {
        $this->doctypeWhitelist = $doctypeWhitelist;
    }

    /**
     * @return array<string>
     */
    public function getDoctypeWhitelist()
    {
        return $this->doctypeWhitelist;
    }

    /**
     * Retrieves internalSubset even in bugfixed php versions
     *
     * @param \DOMDocumentType $child
     * @param string $data
     * @return string
     */
    private function getDomDocumentTypeEntitySubset($data)
    {
        $startPos = $endPos = stripos($data, '<!doctype');
        $braces = 0;
        do {
            $char = $data[$endPos++];
            if ($char === '<') {
                ++$braces;
            }
            if ($char === '>') {
                --$braces;
            }
        } while ($braces > 0);

        $internalSubset = substr($data, $startPos, $endPos - $startPos);
        $internalSubset = str_replace(array("\n", "\r"), '', $internalSubset);
        $internalSubset = preg_replace('/\s{2,}/', ' ', $internalSubset);
        $internalSubset = str_replace(array("[ <!", "> ]>"), array('[<!', '>]>'), $internalSubset);

        return $internalSubset;
    }
}
