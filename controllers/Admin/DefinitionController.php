<?php

use Pimcore\Controller\Action\Admin;
use Pimcore\Model\Object;

/**
 * Controller for Definitions
 *
 * Class AdvancedImportExport_Admin_DefinitionController
 */
class AdvancedImportExport_Admin_DefinitionController extends Admin
{
    public function init()
    {
        parent::init();

        $product = new Pimcore\Model\Object\CoreShopProduct();
        $product->getVariants()->getCoreShopDimensionTest();

        // check permissions
        //TODO: Permissions?
        /*$notRestrictedActions = array('list');
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission('coreshop_permission_carriers');
        }*/
    }

    public function getConfigAction() {
        $this->_helper->json(array(
            'success' => true,
            'providers' => \AdvancedImportExport\Model\AbstractProvider::$availableProviders,
            'interpreter' => \AdvancedImportExport\Model\Interpreter\AbstractInterpreter::$availableInterpreter,
            'cleaner' => \AdvancedImportExport\Model\Cleaner\AbstractCleaner::$availableCleaner
        ));
    }

    public function listAction()
    {
        $list = new \AdvancedImportExport\Model\Definition\Listing();

        $data = array();
        if (is_array($list->getDefinitions())) {
            foreach ($list->getDefinitions() as $definition) {
                $data[] = $this->getTreeNodeConfig($definition);
            }
        }
        $this->_helper->json($data);
    }

    protected function getTreeNodeConfig(\AdvancedImportExport\Model\Definition $definition)
    {
        $tmp = array(
            'id' => $definition->getId(),
            'text' => $definition->getName(),
            'qtipCfg' => array(
                'title' => 'ID: '.$definition->getId(),
            ),
            'name' => $definition->getName(),
        );

        return $tmp;
    }

    public function addAction()
    {
        $name = $this->getParam('name');

        if (strlen($name) <= 0) {
            $this->helper->json(array('success' => false, 'message' => $this->getTranslator()->translate('Name must be set')));
        } else {
            $definition = new \AdvancedImportExport\Model\Definition();
            $definition->setName($name);
            $definition->save();

            $this->_helper->json(array('success' => true, 'data' => $definition));
        }
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $definition = \AdvancedImportExport\Model\Definition::getById($id);

        if ($definition instanceof \AdvancedImportExport\Model\Definition) {
            $this->_helper->json(array('success' => true, 'data' => $definition));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $definition = \AdvancedImportExport\Model\Definition::getById($id);

        if ($data && $definition instanceof \AdvancedImportExport\Model\Definition) {
            $data = \Zend_Json::decode($this->getParam('data'));
            
            $definition->setValues($data);
            $providerClass = 'AdvancedImportExport\\Model\\Provider\\' . ucfirst($definition->getProvider());
            
            if(\Pimcore\Tool::classExists($providerClass)) {
                $provider = new $providerClass();

                if($provider instanceof \AdvancedImportExport\Model\AbstractProvider) {
                    $provider->setValues($data['configuration']);

                    $definition->setProviderConfiguration($provider);
                }
                else {
                    $this->_helper->json(array('success' => false, 'message' => 'Provider Class found, but it needs to inherit from AdvancedImportExport\Model\AbstractProvider'));
                }

                $maps = [];

                foreach($data['mapping'] as $map) {
                    $mapping = new \AdvancedImportExport\Model\Mapping();
                    $mapping->setValues($map);

                    $maps[] = $mapping;
                }

                $definition->setMapping($maps);
            }
            else {
                $this->_helper->json(array('success' => false, 'message' => 'Provider Class not found'));
            }
            
            $definition->save();

            $this->_helper->json(array('success' => true, 'data' => $definition));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $definition = \AdvancedImportExport\Model\Definition::getById($id);

        if ($definition instanceof \AdvancedImportExport\Model\Definition) {
            $definition->delete();

            $this->_helper->json(array('success' => true));
        }

        $this->_helper->json(array('success' => false));
    }

    public function getColumnsAction() {
        $id = $this->getParam('id');

        $definition = \AdvancedImportExport\Model\Definition::getById($id);

        if ($definition instanceof \AdvancedImportExport\Model\Definition) {
            $customFromColumn = new \AdvancedImportExport\Model\Mapping\FromColumn();
            $customFromColumn->setIdentifier('custom');
            $customFromColumn->setLabel('Custom');

            $fromColumns = $definition->getProviderConfiguration()->getColumns();
            $fromColumns[] = $customFromColumn;

            $toColumns = $this->getClassDefinitionForFieldSelection(Object\ClassDefinition::getByName($definition->getClass()));
            $mappings = $definition->getMapping();
            $mappingDefinition = [];
            $fromColumnsResult = [];

            foreach($fromColumns as $fromColumn) {
                $fromColumn = get_object_vars($fromColumn);

                $fromColumn['id'] = $fromColumn['identifier'];

                $fromColumnsResult[] = $fromColumn;
            }

            foreach($toColumns as $classToColumn) {
                $found = false;

                if(is_array($mappings)) {
                    foreach ($mappings as $mapping) {
                        if ($mapping->getToColumn() === $classToColumn->getIdentifier()) {
                            $found = true;

                            $mappingDefinition[] = [
                                'fromColumn' => $mapping->getFromColumn(),
                                'toColumn' => $mapping->getToColumn(),
                                'primaryIdentifier' => $mapping->getPrimaryIdentifier(),
                                'config' => $mapping->getConfig()
                            ];

                            break;
                        }
                    }
                }

                if (!$found) {
                    $mappingDefinition[] = [
                        'fromColumn' => null,
                        'toColumn' => $classToColumn->getIdentifier(),
                        'primaryIdentifier' => false,
                        'config' => $classToColumn->getConfig()
                    ];
                }
            }
            
            $this->_helper->json(array('success' => true, 'mapping' => $mappingDefinition, 'fromColumns' => $fromColumnsResult, 'toColumns' => $toColumns));
        }

        $this->_helper->json(array('success' => false));
    }

    /**
     * @param Object\ClassDefinition $class
     *
     * @return array
     */
    public function getClassDefinitionForFieldSelection(Object\ClassDefinition $class)
    {
        $fields = $class->getFieldDefinitions();

        $systemColumns = [
            "published"
        ];

        $result = array(

        );

        $activatedLanguages = \Pimcore\Tool::getValidLanguages();

        foreach($systemColumns as $sysColumn) {
            $toColumn = new \AdvancedImportExport\Model\Mapping\ToColumn();

            $toColumn->setLabel($sysColumn);
            $toColumn->setFieldtype("input");
            $toColumn->setIdentifier($sysColumn);
            $toColumn->setType("systemColumn");

            $result[] = $toColumn;
        }

        foreach ($fields as $field) {
            if ($field instanceof Object\ClassDefinition\Data\Localizedfields) {
                foreach($activatedLanguages as $language) {

                    $localizedFields = $field->getFieldDefinitions();

                    foreach ($localizedFields as $localizedField) {
                        $localizedField = $this->getFieldConfiguration($localizedField);

                        $localizedField->setType('localizedfield.' . $language);
                        $localizedField->setIdentifier($localizedField->getIdentifier() . "~" . $language);
                        $localizedField->setConfig([
                            "interpreter" => "localizedfield",
                            "language" => $language
                        ]);
                        $result[] = $localizedField;
                    }
                }
            } elseif ($field instanceof Object\ClassDefinition\Data\Objectbricks) {
                $list = new Object\Objectbrick\Definition\Listing();
                $list = $list->load();

                foreach ($list as $brickDefinition) {
                    if ($brickDefinition instanceof Object\Objectbrick\Definition) {
                        $key = $brickDefinition->getKey();
                        $classDefs = $brickDefinition->getClassDefinitions();

                        foreach ($classDefs as $classDef) {
                            if ($classDef['classname'] === $class->getId() && $classDef['fieldname'] === $field->getName()) {
                                $fields = $brickDefinition->getFieldDefinitions();

                                foreach ($fields as $brickField) {
                                    $resultField = $this->getFieldConfiguration($brickField);

                                    $resultField->setType("objectbrick");
                                    $resultField->setIdentifier('objectbrick~' . $field->getName() . '~' . $key . '~' . $resultField->getIdentifier());
                                    $resultField->setConfig([
                                        "class" => $key,
                                        "interpreter" => "objectbrick"
                                    ]);

                                    $result[] = $resultField;
                                }

                                break;
                            }
                        }
                    }
                }
            } elseif ($field instanceof Object\ClassDefinition\Data\Fieldcollections) {
                //TODO: implement FieldCollection
            } elseif ($field instanceof Object\ClassDefinition\Data\Classificationstore) {
                $list = new Object\Classificationstore\GroupConfig\Listing();

                $allowedGroupIds = $field->getAllowedGroupIds();

                if ($allowedGroupIds) {
                    $list->setCondition('ID in ('.implode(',', $allowedGroupIds).')');
                }

                $list->load();

                $groupConfigList = $list->getList();

                foreach ($groupConfigList as $config) {
                    $key = $config->getId().($config->getName() ? $config->getName() : 'EMPTY');

                    foreach ($config->getRelations() as $relation) {
                        if ($relation instanceof Object\Classificationstore\KeyGroupRelation) {
                            $keyId = $relation->getKeyId();

                            $keyConfig = Object\Classificationstore\KeyConfig::getById($keyId);

                            $toColumn = new \AdvancedImportExport\Model\Mapping\ToColumn();
                            $toColumn->setIdentifier('classificationstore~' . $field->getName() . '~' . $keyConfig->getId() . '~' . $config->getId());
                            $toColumn->setType("classificationstore");
                            $toColumn->setFieldtype($keyConfig->getType());
                            $toColumn->setConfig([
                                "keyId" => $keyConfig->getId(),
                                "groupId" => $config->getId(),
                                "interpreter" => "classificationstore"
                            ]);
                            $toColumn->setLabel($keyConfig->getName());

                            $result[] = $toColumn;
                        }
                    }
                }
            } else {
                $result[] = $this->getFieldConfiguration($field);
            }
        }

        return $result;
    }

    /**
     * @param Object\ClassDefinition\Data $field
     * @return \AdvancedImportExport\Model\Mapping\ToColumn
     */
    protected function getFieldConfiguration(Object\ClassDefinition\Data $field)
    {
        $toColumn = new \AdvancedImportExport\Model\Mapping\ToColumn();

        $toColumn->setLabel($field->getName());
        $toColumn->setFieldtype($field->getFieldtype());
        $toColumn->setIdentifier($field->getName());

        return $toColumn;
    }
}