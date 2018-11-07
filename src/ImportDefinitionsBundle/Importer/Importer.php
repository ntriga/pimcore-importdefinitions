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

namespace ImportDefinitionsBundle\Importer;

use CoreShop\Component\Registry\ServiceRegistryInterface;
use ImportDefinitionsBundle\Event\EventDispatcherInterface;
use ImportDefinitionsBundle\Event\ImportDefinitionEvent;
use ImportDefinitionsBundle\Exception\DoNotSetException;
use ImportDefinitionsBundle\Filter\FilterInterface;
use ImportDefinitionsBundle\Loader\LoaderInterface;
use ImportDefinitionsBundle\Model\DataSetAwareInterface;
use ImportDefinitionsBundle\Model\DefinitionInterface;
use ImportDefinitionsBundle\Model\ImportDefinitionInterface;
use ImportDefinitionsBundle\Model\ImportMapping;
use ImportDefinitionsBundle\Model\MappingInterface;
use ImportDefinitionsBundle\Provider\ProviderInterface;
use ImportDefinitionsBundle\Runner\RunnerInterface;
use ImportDefinitionsBundle\Runner\SaveRunnerInterface;
use ImportDefinitionsBundle\Runner\SetterRunnerInterface;
use ImportDefinitionsBundle\Setter\SetterInterface;
use Pimcore\File;
use Pimcore\Mail;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\Document;
use Pimcore\Model\Version;
use Pimcore\Placeholder;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class Importer implements ImporterInterface
{
    /**
     * @var ServiceRegistryInterface
     */
    private $providerRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $filterRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $runnerRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $interpreterRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $setterRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $cleanerRegistry;

    /**
     * @var ServiceRegistryInterface
     */
    private $loaderRegistry;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Importer constructor.
     * @param ServiceRegistryInterface $providerRegistry
     * @param ServiceRegistryInterface $filterRegistry
     * @param ServiceRegistryInterface $runnerRegistry
     * @param ServiceRegistryInterface $interpreterRegistry
     * @param ServiceRegistryInterface $setterRegistry
     * @param ServiceRegistryInterface $cleanerRegistry
     * @param ServiceRegistryInterface $loaderRegistry
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServiceRegistryInterface $providerRegistry,
        ServiceRegistryInterface $filterRegistry,
        ServiceRegistryInterface $runnerRegistry,
        ServiceRegistryInterface $interpreterRegistry,
        ServiceRegistryInterface $setterRegistry,
        ServiceRegistryInterface $cleanerRegistry,
        ServiceRegistryInterface $loaderRegistry,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    )
    {
        $this->providerRegistry = $providerRegistry;
        $this->filterRegistry = $filterRegistry;
        $this->runnerRegistry = $runnerRegistry;
        $this->interpreterRegistry = $interpreterRegistry;
        $this->setterRegistry = $setterRegistry;
        $this->cleanerRegistry = $cleanerRegistry;
        $this->loaderRegistry = $loaderRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function doImport(DefinitionInterface $definition, $params)
    {
        /**
         * @var $definition ImportDefinitionInterface
         */
        Assert::isInstanceOf($definition, ImportDefinitionInterface::class);

        $filter = null;
        $objectIds = [];
        $exceptions = [];

        if ($definition->getCreateVersion()) {
            Version::enable();
        } else {
            Version::disable();
        }

        $filterType = $definition->getFilter();
        if ($filterType) {
            $filter = $this->filterRegistry->get($filterType);
        }

        $data = $this->getData($definition, $params);

        if (\count($data) > 0) {
            $this->eventDispatcher->dispatch($definition, 'import_definition.total', \count($data), $params);

            list($objectIds, $exceptions) = $this->runImport($definition, $params, $filter, $data);
        }

        $cleanerType = $definition->getCleaner();
        if ($cleanerType) {
            $cleaner = $this->cleanerRegistry->get($cleanerType);

            $this->logger->info(sprintf('Running Cleaner "%s"', $cleanerType));
            $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Running Cleaner "%s"', $cleanerType, $params));

            $cleaner->cleanup($definition, $objectIds);

            $this->logger->info(sprintf('Finished Cleaner "%s"', $cleanerType));
            $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Finished Cleaner "%s"', $cleanerType, $params));
        }

        if (\count($exceptions) > 0) {
            $this->sendDocument($definition, Document::getById($definition->getFailureNotificationDocument()), $objectIds, $exceptions);
        } else {
            $this->sendDocument($definition, Document::getById($definition->getSuccessNotificationDocument()), $objectIds, $exceptions);
        }

        $this->eventDispatcher->dispatch($definition, 'import_definition.finished', '', $params);

        return $objectIds;
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param $document
     * @param array $objectIds
     * @param array $exceptions
     * @throws \Exception
     */
    private function sendDocument(ImportDefinitionInterface $definition, $document, $objectIds, $exceptions)
    {
        if ($document instanceof Document) {
            $params = [
                'exceptions' => $exceptions,
                'objectIds' => $objectIds,
                'className' => $definition->getClass(),
                'countObjects' => \count($objectIds),
                'countExceptions' => \count($exceptions),
                'name' => $definition->getName(),
                'provider' => $definition->getProvider()
            ];

            if ($document instanceof Document\Email) {
                $mail = new Mail();
                $mail->setDocument($document);
                $mail->setParams($params);

                $mail->send();
            } elseif (is_a($document, "\\Pimcore\\Model\\Document\\Pushover")) {
                $document->send($params);
            }
        }
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param $params
     * @return array
     */
    private function getData(ImportDefinitionInterface $definition, $params)
    {
        /** @var ProviderInterface $provider */
        $provider = $this->providerRegistry->get($definition->getProvider());

        return $provider->getData($definition->getConfiguration(), $definition, $params);
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param $params
     * @param null $filter
     * @param array $dataSet
     * @throws \Exception
     * @return array
     */
    private function runImport(ImportDefinitionInterface $definition, $params, $filter = null, array $dataSet = [])
    {
        $count = 0;
        $countToClean = 1000;
        $objectIds = [];
        $exceptions = [];

        foreach ($dataSet as $row) {
            try {
                $object = $this->importRow($definition, $row, $dataSet, $params, $filter);

                if ($object instanceof Concrete) {
                    $objectIds[] = $object->getId();
                }

                if (($count + 1) % $countToClean === 0) {
                    \Pimcore::collectGarbage();
                    $this->logger->info('Clean Garbage');
                    $this->eventDispatcher->dispatch($definition, 'import_definition.status', 'Collect Garbage', $params);
                }

                $count++;
            } catch (\Exception $ex) {
                $this->logger->error($ex);

                $exceptions[] = $ex;

                $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Error: %s', $ex->getMessage()), $params);

                if ($definition->getStopOnException()) {
                    throw $ex;
                }
            }

            $this->eventDispatcher->dispatch($definition, 'import_definition.progress', '', $params);
        }

        return [$objectIds, $exceptions];
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param array $data
     * @param array $dataSet
     * @param $params
     * @param null $filter
     * @return null|Concrete
     * @throws \Exception
     */
    private function importRow(ImportDefinitionInterface $definition, $data, $dataSet, $params, $filter = null)
    {
        $runner = null;

        $object = $this->getObject($definition, $data, $params);

        if (null !== $object && !$object->getId()) {
            if ($definition->getSkipNewObjects()) {
                $this->eventDispatcher->dispatch($definition, 'import_definition.status', 'Ignoring new Object', $params);
                return null;
            }
        } else {
            if ($definition->getSkipExistingObjects()) {
                $this->eventDispatcher->dispatch($definition, 'import_definition.status', 'Ignoring existing Object', $params);
                return null;
            }
        }

        if ($filter instanceof FilterInterface) {
            if ($filter instanceof DataSetAwareInterface) {
                $filter->setDataSet($dataSet);
            }

            if (!$filter->filter($definition, $data, $object)) {
                $this->eventDispatcher->dispatch($definition, 'import_definition.status', 'Filtered Object', $params);
                return null;
            }
        }

        $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Import Object %s', ($object->getId() ? $object->getFullPath() : 'new')), $params);
        $this->eventDispatcher->dispatch($definition, 'import_definition.object.start', $object, $params);

        if ($definition->getRunner()) {
            $runner = $this->runnerRegistry->get($definition->getRunner());
        }


        if ($runner instanceof RunnerInterface) {
            if ($runner instanceof DataSetAwareInterface) {
                $runner->setDataSet($dataSet);
            }

            $runner->preRun($object, $data, $definition, $params);
        }

        $this->logger->info(sprintf('Imported Object: %s', $object->getRealFullPath()));

        /**
         * @var $mapItem ImportMapping
         */
        foreach ($definition->getMapping() as $mapItem) {
            $value = null;

            if (array_key_exists($mapItem->getFromColumn(), $data)) {
                $value = $data[$mapItem->getFromColumn()];
            }

            $this->setObjectValue($object, $mapItem, $value, $data, $dataSet, $definition, $params, $runner);
        }

        $shouldSave = true;
        if ($runner instanceof SaveRunnerInterface) {
            if ($runner instanceof DataSetAwareInterface) {
                $runner->setDataSet($dataSet);
            }

            $shouldSave = $runner->shouldSaveObject($object,$data, $definition, $params);
        }

        if ($shouldSave) {
            $object->setUserModification(0); //Set User to "system"
            $object->setOmitMandatoryCheck($definition->getOmitMandatoryCheck());
            $object->save();

            $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Imported Object %s', $object->getFullPath()), $params);
        } else {
            $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Skipped Object %s', $object->getFullPath()), $params);
        }

        $this->eventDispatcher->dispatch($definition, 'import_definition.status', sprintf('Imported Object %s', $object->getFullPath()), $params);
        $this->eventDispatcher->dispatch($definition, 'import_definition.object.finished', $object, $params);

        if ($runner instanceof RunnerInterface) {
            if ($runner instanceof DataSetAwareInterface) {
                $runner->setDataSet($dataSet);
            }

            $runner->postRun($object, $data, $definition, $params);
        }

        return $object;
    }

    /**
     * @param Concrete $object
     * @param MappingInterface $map
     * @param $value
     * @param array $data
     * @param array $dataSet
     * @param ImportDefinitionInterface $definition
     * @param $params
     * @param RunnerInterface $runner
     */
    private function setObjectValue(Concrete $object, ImportMapping $map, $value, $data, $dataSet, ImportDefinitionInterface $definition, $params, RunnerInterface $runner = null)
    {
        if ($map->getInterpreter()) {
            try {
                $interpreter = $this->interpreterRegistry->get($map->getInterpreter());

                if ($interpreter instanceof DataSetAwareInterface) {
                    $interpreter->setDataSet($dataSet);
                }

                $value = $interpreter->interpret($object, $value, $map, $data, $definition, $params, $map->getInterpreterConfig());
            }
            catch (DoNotSetException $ex) {
                return;
            }

        }

        if ($map->getToColumn() === 'o_type' && $map->getSetter() !== 'object_type') {
            throw new \InvalidArgumentException('Type has to be used with ObjectType Setter!');
        }

        $shouldSetField = true;

        if ($runner instanceof SetterRunnerInterface) {
            if ($runner instanceof DataSetAwareInterface) {
                $runner->setDataSet($dataSet);
            }

            $shouldSetField = $runner->shouldSetField($object, $map, $value, $data, $definition, $params);
        }

        if (!$shouldSetField) {
            return;
        }

        if ($map->getSetter()) {
            $setter = $this->setterRegistry->get($map->getSetter());

            if ($setter instanceof SetterInterface) {
                if ($setter instanceof DataSetAwareInterface) {
                    $setter->setDataSet($dataSet);
                }

                $setter->set($object, $value, $map, $data);
            }
        } else {
            $object->setValue($map->getToColumn(), $value);
        }
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param                           $data
     * @param                           $params
     * @return Concrete
     * @throws \Exception
     */
    private function getObject(ImportDefinitionInterface $definition, $data, $params)
    {
        $class = $definition->getClass();
        $classObject = '\Pimcore\Model\DataObject\\' . ucfirst($class);
        $classDefinition = ClassDefinition::getByName($class);
        $obj = null;

        if (!$classDefinition instanceof ClassDefinition) {
            throw new \InvalidArgumentException(sprintf('Class not found %s', $class));
        }

        /**
         * @var $loader LoaderInterface
         */
        if ($definition->getLoader()) {
            $loader = $this->loaderRegistry->get($definition->getLoader());
        }
        else {
            $loader = $this->loaderRegistry->get('primary_key');
        }

        $obj = $loader->load($class, $data, $definition, $params);

        if (null === $obj) {
            $obj = new $classObject();
        }

        $key = Service::getValidKey($this->createKey($definition, $data), 'object');

        if ($definition->getRelocateExistingObjects() || !$obj->getId()) {
            $obj->setParent(Service::createFolderByPath($this->createPath($definition, $data)));
        }

        if ($definition->getRenameExistingObjects() || !$obj->getId()) {
            if ($key && $definition->getKey()) {
                $obj->setKey($key);
            } else {
                $obj->setKey(File::getValidFilename(uniqid()));
            }
        }

        if (!$obj->getKey()) {
            throw new \InvalidArgumentException('No key set, please check your import-data');
        }

        $obj->setKey(Service::getUniqueKey($obj));

        return $obj;
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param array $data
     * @return string
     */
    private function createPath(ImportDefinitionInterface $definition, $data)
    {
        $placeholderHelper = new Placeholder();
        return $placeholderHelper->replacePlaceholders($definition->getObjectPath(), $data);
    }

    /**
     * @param ImportDefinitionInterface $definition
     * @param array $data
     * @return string
     */
    private function createKey(ImportDefinitionInterface $definition, $data)
    {
        $placeholderHelper = new Placeholder();
        return $placeholderHelper->replacePlaceholders($definition->getKey(), $data);
    }
}