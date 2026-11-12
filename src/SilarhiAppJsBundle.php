<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle;

use Override;
use Silarhi\AppJsBundle\Command\DumpEnumsCommand;
use Silarhi\AppJsBundle\Command\DumpFormsCommand;
use Silarhi\AppJsBundle\DependencyInjection\FormToJsPass;
use Silarhi\AppJsBundle\Enum\EnumDumper;
use Silarhi\AppJsBundle\Enum\EnumLocator;
use Silarhi\AppJsBundle\Form\FormShapeDumper;
use Silarhi\AppJsBundle\Twig\AppJsExtension;
use Silarhi\AppJsBundle\Writer\Format;
use Silarhi\AppJsBundle\Writer\GeneratedFilesWriter;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Environment;

final class SilarhiAppJsBundle extends AbstractBundle
{
    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new FormToJsPass());
    }

    #[Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('enums')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('output_dir')
                            ->info('The directory of the dumped enums, owned by app-js:dump-enums.')
                            ->defaultValue('%kernel.project_dir%/assets/js/enums')
                            ->cannotBeEmpty()
                        ->end()
                        ->enumNode('format')
                            ->info('ts: .ts modules, js: .js modules (the same code).')
                            ->values(['ts', 'js'])
                            ->defaultValue('ts')
                        ->end()
                        ->arrayNode('paths')
                            ->info('The directories scanned for enums, with their namespace prefix.')
                            ->useAttributeAsKey('directory')
                            ->normalizeKeys(false)
                            ->defaultValue(['%kernel.project_dir%/src/Enum' => 'App\\Enum'])
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('forms')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('output_dir')
                            ->info('The directory of the dumped form shapes, owned by app-js:dump-forms.')
                            ->defaultValue('%kernel.project_dir%/assets/js/forms')
                            ->cannotBeEmpty()
                        ->end()
                        ->enumNode('format')
                            ->info('ts: .ts files, js: .d.ts declarations for JSDoc (a shape is a type only).')
                            ->values(['ts', 'js'])
                            ->defaultValue('ts')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array{enums: array{output_dir: string, format: 'ts'|'js', paths: array<string, string>}, forms: array{output_dir: string, format: 'ts'|'js'}} $config */
        $services = $container->services();
        $formAvailable = interface_exists(FormFactoryInterface::class);

        $services->set('silarhi_app_js.writer', GeneratedFilesWriter::class);

        $services->set('silarhi_app_js.enum_locator', EnumLocator::class)
            ->args([$config['enums']['paths']]);
        $services->set('silarhi_app_js.enum_dumper', EnumDumper::class)
            ->args([service('silarhi_app_js.enum_locator'), Format::from($config['enums']['format'])]);
        $services->set('silarhi_app_js.command.dump_enums', DumpEnumsCommand::class)
            ->args([
                service('silarhi_app_js.enum_dumper'),
                service('silarhi_app_js.writer'),
                $config['enums']['output_dir'],
            ])
            ->tag('console.command');

        if (class_exists(Environment::class)) {
            $services->set('silarhi_app_js.twig_extension', AppJsExtension::class)
                ->tag('twig.extension');
        }

        if (!$formAvailable) {
            return;
        }

        $services->set(FormToJsPass::DUMPER, FormShapeDumper::class)
            ->arg('$formFactory', service('form.factory'))
            ->arg('$formRegistry', service('form.registry'))
            ->arg('$variantProviders', new AbstractArgument('set by FormToJsPass'))
            ->arg('$forms', new AbstractArgument('set by FormToJsPass'))
            ->arg('$format', Format::from($config['forms']['format']));
        $services->set(FormToJsPass::COMMAND, DumpFormsCommand::class)
            ->args([
                service(FormToJsPass::DUMPER),
                service('silarhi_app_js.writer'),
                $config['forms']['output_dir'],
            ])
            ->tag('console.command');
    }
}
