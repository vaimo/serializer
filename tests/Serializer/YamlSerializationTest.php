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

namespace JMS\Serializer\Tests\Serializer;

use JMS\Serializer\Exception\RuntimeException;

class YamlSerializationTest extends BaseSerializationTest
{
    public function testConstraintViolation()
    {
        $this->markTestSkipped('This is not available for the YAML format.');
    }

    public function testConstraintViolationList()
    {
        $this->markTestSkipped('This is not available for the YAML format.');
    }

    public function testFormErrors()
    {
        $this->markTestSkipped('This is not available for the YAML format.');
    }

    public function testNestedFormErrors()
    {
        $this->markTestSkipped('This is not available for the YAML format.');
    }

    public function testFormErrorsWithNonFormComponents()
    {
        $this->markTestSkipped('This is not available for the YAML format.');
    }

    protected function getContent($key)
    {
        if (!file_exists($file = __DIR__ . '/yml/' . $key . '.yml')) {
            throw new RuntimeException(sprintf('The content with key "%s" does not exist.', $key));
        }

        return file_get_contents($file);
    }

    public function getTypeHintedArrays()
    {
        return [

            [[1, 2], "- 1\n- 2\n", null],
            [['a', 'b'], "- a\n- b\n", null],
            [['a' => 'a', 'b' => 'b'], "a: a\nb: b\n", null],

            [[], " []\n", null],
            [[], " []\n", 'array'],
            [[], " []\n", 'array<integer>'],
            [[], " {}\n", 'array<string,integer>'],


            [[1, 2], "- 1\n- 2\n", 'array'],
            [[1 => 1, 2 => 2], "1: 1\n2: 2\n", 'array'],
            [[1 => 1, 2 => 2], "- 1\n- 2\n", 'array<integer>'],
            [['a', 'b'], "- a\n- b\n", 'array<string>'],

            [[1 => 'a', 2 => 'b'], "- a\n- b\n", 'array<string>'],
            [['a' => 'a', 'b' => 'b'], "- a\n- b\n", 'array<string>'],


            [[1, 2], "0: 1\n1: 2\n", 'array<integer,integer>'],
            [[1, 2], "0: 1\n1: 2\n", 'array<string,integer>'],
            [[1, 2], "0: 1\n1: 2\n", 'array<string,string>'],


            [['a', 'b'], "0: a\n1: b\n", 'array<integer,string>'],
            [['a' => 'a', 'b' => 'b'], "a: a\nb: b\n", 'array<string,string>'],
        ];
    }

    /**
     * @dataProvider getTypeHintedArrays
     * @param array $array
     * @param string $expected
     * @param string|null $hint
     */
    public function testTypeHintedArraySerialization(array $array, $expected, $hint = null)
    {
        $this->assertEquals($expected, $this->serialize($array, null, $hint));
    }


    protected function getFormat()
    {
        return 'yml';
    }

    protected function hasDeserializer()
    {
        return false;
    }
}
