<?php
/**
 * Import Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016-2018 w-vision AG (https://www.w-vision.ch)
 * @license    https://github.com/w-vision/ImportDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ImportDefinitionsBundle\Model\ExportMapping;

use ImportDefinitionsBundle\Model\AbstractColumn;

class FromColumn extends AbstractColumn
{
    /**
     * @var null|string
     */
    public $type;

    /**
     * @var string
     */
    public $label;

    /**
     * @var null|string
     */
    public $fieldtype;

    /**
     * @var null|array
     */
    public $config;

    /**
     * @var null|string
     */
    public $interpreter;

    /**
     * @var null|array
     */
    public $interpreterConfig;

    /**
     * @var null|string
     */
    public $getter;

    /**
     * @var null|array
     */
    public $getterConfig;

    /**
     * @var null|string
     */
    public $group;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return null|string
     */
    public function getFieldtype()
    {
        return $this->fieldtype;
    }

    /**
     * @param string $fieldtype
     */
    public function setFieldtype($fieldtype)
    {
        $this->fieldtype = $fieldtype;
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array|null
     */
    public function getInterpreterConfig()
    {
        return $this->interpreterConfig;
    }

    /**
     * @param array $interpreterConfig
     */
    public function setInterpreterConfig($interpreterConfig)
    {
        $this->interpreterConfig = $interpreterConfig;
    }

    /**
     * @return null|string
     */
    public function getInterpreter()
    {
        return $this->interpreter;
    }

    /**
     * @param string $interpreter
     */
    public function setInterpreter($interpreter)
    {
        $this->interpreter = $interpreter;
    }

    /**
     * @return array|null
     */
    public function getGetterConfig()
    {
        return $this->getterConfig;
    }

    /**
     * @param array $getterConfig
     */
    public function setGetterConfig($getterConfig)
    {
        $this->getterConfig = $getterConfig;
    }

    /**
     * @return null|string
     */
    public function getGetter()
    {
        return $this->getter;
    }

    /**
     * @param string $getter
     */
    public function setGetter($getter)
    {
        $this->getter = $getter;
    }

    /**
     * @return null|string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group)
    {
        $this->group = $group;
    }
}
