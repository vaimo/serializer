<?php
namespace JMS\Serializer;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

trait DeserializationLegacyTrait
{
    /**
     * @deprecated
     */
    public function prepare($data)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->prepareData($data);
    }

    /**
     * @deprecated
     */
    public function visitNull($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeNull($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitString($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeString($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeBoolean($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitDouble($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeFloat($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitInteger($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeInteger($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitArray($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->deserializeArray($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->startDeserializingObject($metadata, $data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->deserializeProperty($metadata, $data, $context);
    }

    /**
     * @deprecated
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->endDeserializingObject($metadata, $data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function setNavigator(GraphNavigatorInterface $navigator)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->initialize($navigator);
    }

    /**
     * @deprecated
     */
    public function getResult()
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        throw new RuntimeException(__METHOD__ . " has been deprecated for deserialization visitors");
    }
}
