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
 * @copyright  Copyright (c) 2017 Divante (http://www.divante.co)
 * @license    https://github.com/w-vision/ImportDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ImportDefinitionsBundle\Interpreter;

use ImportDefinitionsBundle\Model\DefinitionInterface;
use ImportDefinitionsBundle\Model\Mapping;
use Pimcore\Model\DataObject\Concrete;
use ImportDefinitionsBundle\Service\Placeholder;

class MultipleAssetsInterpreter implements InterpreterInterface
{
    /**
     * {@inheritdoc}
     */
    public function interpret(
        Concrete $object,
        $value,
        Mapping $map,
        $data,
        DefinitionInterface $definition,
        $params,
        $configuration
    ) {

        $paths = array_filter(explode(',' , $value));
        $items = [];
        foreach ($paths as $path) {
            $assetFullPath = $configuration['path'] . "/" . $path;
            $items[] = \Pimcore\Model\Asset::getByPath($assetFullPath);
        }

        return $items;
    }
}
