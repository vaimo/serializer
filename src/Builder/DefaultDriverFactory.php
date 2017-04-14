<?php

namespace JMS\Serializer\Builder;

use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Metadata\Driver\XmlDriver;
use JMS\Serializer\Metadata\Driver\YamlDriver;
use JMS\Serializer\TypeParser;
use Metadata\Driver\DriverChain;
use Metadata\Driver\FileLocator;

final class DefaultDriverFactory implements DriverFactoryInterface
{
    private $typeParser;

    public function __construct(TypeParser $typeParser = null)
    {
        $this->typeParser = $typeParser;
    }

    public function createDriver(array $metadataDirs, Reader $annotationReader)
    {
        if ($this->typeParser === null) {
            $this->typeParser = new TypeParser();
        }

        if (!empty($metadataDirs)) {
            $fileLocator = new FileLocator($metadataDirs);

            return new DriverChain(array(
                new YamlDriver($fileLocator, $this->typeParser),
                new XmlDriver($fileLocator, $this->typeParser),
                new AnnotationDriver($annotationReader, $this->typeParser),
            ));
        }

        return new AnnotationDriver($annotationReader, $this->typeParser);
    }
}
