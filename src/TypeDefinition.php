<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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
use JMS\Serializer\Exception\RuntimeException;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
final class TypeDefinition
{
    protected $name;
    protected $params = array();


    public function __construct($name, array $params = array())
    {
        $this->name = $name;
        $this->params = $params;
    }

    public static function getUnknown()
    {
        return new self('UNKNOWN');
    }


    public function isUnknown()
    {
        return !$this->name || $this->name === 'UNKNOWN';
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array|TypeDefinition[]
     */
    public function getParams()
    {
        return $this->params;
    }

    public function hasParam($index)
    {
        return isset($this->params[$index]);
    }

    /**
     * @param $index
     * @return string|TypeDefinition|null
     */
    public function getParam($index)
    {
        if (!isset($this->params[$index])) {
            throw new RuntimeException("No param $index");
        }
        return $this->params[$index];
    }

    /**
     * @deprecated
     * @return array|null
     */
    public function getArray()
    {
        if ($this->name === 'UNKNOWN') {
            return null;
        }
        $params = [];
        foreach ($this->params as $k => $param){
            if ($param instanceof self){
                $params[$k] = $param->getArray();
            } else {
                $params[$k] = $param;
            }

        }
        return [
            'name' => $this->getName(),
            'params' => $params
        ];
    }

    /**
     * @deprecated
     *
     * @param array|null $type
     * @return TypeDefinition
     */
    public static function fromArray(array $type = null )
    {
        if ($type === null || !isset($type['name'])) {
            return self::getUnknown();
        }
        $params = array();
        if (!empty($type['params'])) {
            foreach ($type['params'] as $k => $param) {
                if(isset($param['name'])){
                    $params[$k] = self::fromArray($param);
                }else{
                    $params[$k] = $param;
                }
            }
        }

        return new self($type['name'], $params);
    }
}
