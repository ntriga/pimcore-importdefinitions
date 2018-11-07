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

use CoreShop\Component\Registry\ServiceRegistryInterface;
use ImportDefinitionsBundle\Model\DataSetAwareInterface;
use ImportDefinitionsBundle\Model\DataSetAwareTrait;
use ImportDefinitionsBundle\Model\DefinitionInterface;
use ImportDefinitionsBundle\Model\Mapping;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionalInterpreter implements InterpreterInterface, DataSetAwareInterface
{
    use DataSetAwareTrait;

    /**
     * @var ServiceRegistryInterface
     */
    private $interpreterRegistry;

    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ServiceRegistryInterface $interpreterRegistry
     * @param ExpressionLanguage       $expressionLanguage
     * @param ContainerInterface       $container
     */
    public function __construct(ServiceRegistryInterface $interpreterRegistry, ExpressionLanguage $expressionLanguage, ContainerInterface $container)
    {
        $this->interpreterRegistry = $interpreterRegistry;
        $this->expressionLanguage = $expressionLanguage;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function interpret(Concrete $object, $value, Mapping $map, $data, DefinitionInterface $definition, $params, $configuration)
    {
        $params = [
            'value' => $value,
            'object' => $object,
            'map' => $map,
            'data' => $data,
            'definition' => $definition,
            'params' => $params,
            'configuration' => $configuration,
            'container' => $this->container
        ];

        $condition = $configuration['condition'];

        if ($this->expressionLanguage->evaluate($condition, $params)) {
            $interpreter = $configuration['true_interpreter'];
        }
        else {
            $interpreter = $configuration['false_interpreter'];
        }

        $interpreterObject = $this->interpreterRegistry->get($interpreter['type']);

        if (!$interpreterObject instanceof InterpreterInterface) {
            return $value;
        }

        if ($interpreterObject instanceof DataSetAwareInterface) {
            $interpreterObject->setDataSet($this->getDataSet());
        }

        return $interpreterObject->interpret($object, $value, $map, $data, $definition, $params, $interpreter['interpreterConfig']);
    }
}
