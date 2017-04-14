<?php
namespace JMS\Serializer;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

trait LegacyTrait
{
    /**
     * @deprecated
     */
    protected function getElementType($typeArray)
    {
        trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);

        if (false === isset($typeArray['params'][0])) {
            return null;
        }

        if (isset($typeArray['params'][1]) && is_array($typeArray['params'][1])) {
            return $typeArray['params'][1];
        } else {
            return $typeArray['params'][0];
        }
    }

    /**
     * @deprecated
     */
    public function prepare($data)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $data;
    }

    /**
     * @deprecated
     */
    public function visitNull($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeNull(TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitString($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeString($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeBoolean($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitDouble($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeFloat($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitInteger($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeInteger($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitArray($data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->serializeArray($data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function startVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->startSerializingObject($metadata, $data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->serializeProperty($metadata, $data, $context);
    }

    /**
     * @deprecated
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        return $this->endSerializingObject($metadata, $data, TypeDefinition::fromArray($type), $context);
    }

    /**
     * @deprecated
     */
    public function setNavigator(GraphNavigatorInterface $navigator)
    {
        @trigger_error(__METHOD__ . " is deprecated and will be removed in 3.0", E_USER_DEPRECATED);
        $this->initialize($navigator);
    }
}
