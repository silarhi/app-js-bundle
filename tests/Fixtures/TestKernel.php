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

namespace Silarhi\AppJsBundle\Tests\Fixtures;

use Composer\InstalledVersions;

use function dirname;

use Silarhi\AppJsBundle\SilarhiAppJsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SilarhiAppJsBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'http_method_override' => false,
            'php_errors' => ['log' => true],
            'handle_all_throwables' => true,
            'form' => ['enabled' => true, 'csrf_protection' => true],
            'csrf_protection' => true,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        // The "js" environment dumps JavaScript
        $format = 'js' === $this->environment ? 'js' : 'ts';
        // Symfony 6.4 to 7.2 registers no enum in the container: they are scanned instead
        $paths = version_compare((string) InstalledVersions::getVersion('symfony/dependency-injection'), '7.3', '>=')
            ? []
            : ['%kernel.project_dir%/tests/Fixtures/Enum' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Enum'];
        $container->extension('silarhi_app_js', [
            'enums' => [
                'output_dir' => '%kernel.cache_dir%/app_js/enums',
                'format' => $format,
                'paths' => $paths,
            ],
            'forms' => [
                'output_dir' => '%kernel.cache_dir%/app_js/forms',
                'format' => $format,
            ],
        ]);

        // As an application's services.yaml loads src/: the enums are found through their definitions
        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->load('Silarhi\\AppJsBundle\\Tests\\Fixtures\\Enum\\', __DIR__ . '/Enum/*');
        $services->load('Silarhi\\AppJsBundle\\Tests\\Fixtures\\Form\\', __DIR__ . '/Form/*');
    }
}
