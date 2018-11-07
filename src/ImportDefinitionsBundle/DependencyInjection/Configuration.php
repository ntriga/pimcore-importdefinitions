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

namespace ImportDefinitionsBundle\DependencyInjection;

use CoreShop\Bundle\ResourceBundle\CoreShopResourceBundle;
use CoreShop\Component\Resource\Factory\Factory;
use ImportDefinitionsBundle\Controller\ExportDefinitionController;
use ImportDefinitionsBundle\Form\Type\ExportDefinitionType;
use ImportDefinitionsBundle\Model\ExportDefinition;
use ImportDefinitionsBundle\Model\ExportDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use ImportDefinitionsBundle\Controller\ImportDefinitionController;
use ImportDefinitionsBundle\Form\Type\ImportDefinitionType;
use ImportDefinitionsBundle\Model\ImportDefinition;
use ImportDefinitionsBundle\Model\ImportDefinitionInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wvision_import_definitions');

        $rootNode
            ->children()
                ->scalarNode('driver')->defaultValue(CoreShopResourceBundle::DRIVER_PIMCORE)->end()
            ->end()
        ;

        $this->addPimcoreResourcesSection($rootNode);
        $this->addModelsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addModelsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('definition') //TODO: 3.0.0, rename to import_definition
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->scalarNode('permission')->defaultValue('import_definition')->cannotBeOverwritten()->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(ImportDefinition::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(ImportDefinitionInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('admin_controller')->defaultValue(ImportDefinitionController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(ImportDefinitionType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('export_definition')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->scalarNode('permission')->defaultValue('export_definition')->cannotBeOverwritten()->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(ExportDefinition::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(ExportDefinitionInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('admin_controller')->defaultValue(ExportDefinitionController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(ExportDefinitionType::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addPimcoreResourcesSection(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode('pimcore_admin')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('js')
                        ->addDefaultsIfNotSet()
                        ->ignoreExtraKeys(false)
                        ->children()
                            ->scalarNode('startup')->defaultValue('/bundles/importdefinitions/pimcore/js/startup.js')->end()
                            ->scalarNode('import_definition_panel')->defaultValue('/bundles/importdefinitions/pimcore/js/import/panel.js')->end()
                            ->scalarNode('import_definition_item')->defaultValue('/bundles/importdefinitions/pimcore/js/import/item.js')->end()
                            ->scalarNode('import_definition_config')->defaultValue('/bundles/importdefinitions/pimcore/js/import/configDialog.js')->end()
                            ->scalarNode('export_definition_panel')->defaultValue('/bundles/importdefinitions/pimcore/js/export/panel.js')->end()
                            ->scalarNode('export_definition_item')->defaultValue('/bundles/importdefinitions/pimcore/js/export/item.js')->end()
                            ->scalarNode('export_definition_config')->defaultValue('/bundles/importdefinitions/pimcore/js/export/configDialog.js')->end()
                            ->scalarNode('export_definition_fields')->defaultValue('/bundles/importdefinitions/pimcore/js/export/fields.js')->end()
                            ->scalarNode('import_provider_abstract')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/abstractprovider.js')->end()
                            ->scalarNode('import_provider_csv')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/csv.js')->end()
                            ->scalarNode('import_provider_sql')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/sql.js')->end()
                            ->scalarNode('import_provider_external_sql')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/externalSql.js')->end()
                            ->scalarNode('import_provider_json')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/json.js')->end()
                            ->scalarNode('import_provider_xml')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/xml.js')->end()
                            ->scalarNode('import_provider_raw')->defaultValue('/bundles/importdefinitions/pimcore/js/provider/raw.js')->end()
                            ->scalarNode('export_provider_abstract')->defaultValue('/bundles/importdefinitions/pimcore/js/export_provider/abstractprovider.js')->end()
                            ->scalarNode('export_provider_csv')->defaultValue('/bundles/importdefinitions/pimcore/js/export_provider/csv.js')->end()
                            ->scalarNode('resource_definition')->defaultValue('/bundles/importdefinitions/pimcore/js/resource/definition.js')->end()
                            ->scalarNode('definition_panel')->defaultValue('/bundles/importdefinitions/pimcore/js/import/panel.js')->end()
                            ->scalarNode('definition_item')->defaultValue('/bundles/importdefinitions/pimcore/js/import/item.js')->end()
                            ->scalarNode('definition_config')->defaultValue('/bundles/importdefinitions/pimcore/js/import/configDialog.js')->end()
                            ->scalarNode('interpreter_abstract')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/abstract.js')->end()
                            ->scalarNode('interpreter_href')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/href.js')->end()
                            ->scalarNode('interpreter_multihref')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/multihref.js')->end()
                            ->scalarNode('interpreter_defaultvalue')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/defaultvalue.js')->end()
                            ->scalarNode('interpreter_specificobject')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/specificobject.js')->end()
                            ->scalarNode('interpreter_assetbypath')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/assetbypath.js')->end()
                            ->scalarNode('interpreter_asseturl')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/asseturl.js')->end()
                            ->scalarNode('interpreter_assetsurl')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/assetsurl.js')->end()
                            ->scalarNode('interpreter_quantityvalue')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/quantityvalue.js')->end()
                            ->scalarNode('interpreter_nested')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/nested.js')->end()
                            ->scalarNode('interpreter_nested_container')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/nestedcontainer.js')->end()
                            ->scalarNode('interpreter_empty')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/empty.js')->end()
                            ->scalarNode('interpreter_expression')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/expression.js')->end()
                            ->scalarNode('interpreter_object_resolver')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/objectresolver.js')->end()
                            ->scalarNode('interpreter_mapping')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/mapping.js')->end()
                            ->scalarNode('interpreter_iterator')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/iterator.js')->end()
                            ->scalarNode('interpreter_definition')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/definition.js')->end()
                            ->scalarNode('interpreter_conditional')->defaultValue('/bundles/importdefinitions/pimcore/js/interpreters/conditional.js')->end()
                            ->scalarNode('setter_abstract')->defaultValue('/bundles/importdefinitions/pimcore/js/setters/abstract.js')->end()
                            ->scalarNode('setter_fieldcollection')->defaultValue('/bundles/importdefinitions/pimcore/js/setters/fieldcollection.js')->end()
                            ->scalarNode('setter_objectbrick')->defaultValue('/bundles/importdefinitions/pimcore/js/setters/objectbrick.js')->end()
                            ->scalarNode('setter_classificationstore')->defaultValue('/bundles/importdefinitions/pimcore/js/setters/classificationstore.js')->end()
                            ->scalarNode('setter_localizedfield')->defaultValue('/bundles/importdefinitions/pimcore/js/setters/localizedfield.js')->end()
                            ->scalarNode('getter_fieldcollection')->defaultValue('/bundles/importdefinitions/pimcore/js/getters/fieldcollection.js')->end()
                            ->scalarNode('getter_objectbrick')->defaultValue('/bundles/importdefinitions/pimcore/js/getters/objectbrick.js')->end()
                            ->scalarNode('getter_classificationstore')->defaultValue('/bundles/importdefinitions/pimcore/js/getters/classificationstore.js')->end()
                            ->scalarNode('getter_localizedfield')->defaultValue('/bundles/importdefinitions/pimcore/js/getters/localizedfield.js')->end()
                            ->scalarNode('fetcher_abstract')->defaultValue('/bundles/importdefinitions/pimcore/js/fetchers/abstract.js')->end()
                            ->scalarNode('fetcher_objects')->defaultValue('/bundles/importdefinitions/pimcore/js/fetchers/objects.js')->end()
                        ->end()
                    ->end()
                    ->arrayNode('css')
                        ->addDefaultsIfNotSet()
                        ->ignoreExtraKeys(false)
                        ->children()
                            ->scalarNode('import_definition')->defaultValue('/bundles/importdefinitions/pimcore/css/importdefinition.css')->end()
                        ->end()
                    ->end()
                    ->arrayNode('install')
                        ->addDefaultsIfNotSet()
                        ->ignoreExtraKeys(false)
                        ->children()
                            ->scalarNode('sql')->defaultValue(['@ImportDefinitionsBundle/Resources/install/pimcore/sql/data.sql'])->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
