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

namespace ImportDefinitionsBundle\Interpreter;

use ImportDefinitionsBundle\Model\DataSetAwareInterface;
use ImportDefinitionsBundle\Model\DataSetAwareTrait;
use ImportDefinitionsBundle\Model\DefinitionInterface;
use ImportDefinitionsBundle\Model\Mapping;
use Pimcore\Model\DataObject\Concrete;

class MappingInterpreter implements InterpreterInterface, DataSetAwareInterface
{
    use DataSetAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function interpret(Concrete $object, $value, Mapping $map, $data, DefinitionInterface $definition, $params, $configuration)
    {
        $map = $configuration['mapping'];
        $resolvedMap = [];

        if (!is_array($map)) {
            $map = [];
        }

        foreach ($map as $itemMap) {
            $resolvedMap[$itemMap['from']] = $itemMap['to'];
        }

        if (array_key_exists($value, $resolvedMap)) {
            return $resolvedMap[$value];
        }

        if ($configuration['return_null_when_not_found']) {
            return null;
        }

        return $value;
    }
}
