<?php

declare(strict_types=1);

/**
 * Derafu: Project - Template of a Derafu library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsProject;

use Derafu\Project\Contract\TeapotInterface;
use Derafu\Project\Teapot;
use Derafu\Project\Translation\ProjectTranslationResourceProvider;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads the package's real configuration through a YAML consumer fixture.
 * Keep application services, translators, overrides and test locators in YAML;
 * only container bootstrapping belongs in this test's PHP infrastructure.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function loadContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/fixtures/services')))
            ->load('services.yaml');

        return $container;
    }

    /** @return ServiceLocator<object> */
    private function services(ContainerBuilder $container): ServiceLocator
    {
        $container->compile();
        $services = $container->get('test.project_services');
        self::assertInstanceOf(ServiceLocator::class, $services);

        return $services;
    }

    public function testEveryConfiguredServiceCanBeConstructed(): void
    {
        $services = $this->services($this->loadContainer());
        foreach (array_keys($services->getProvidedServices()) as $id) {
            self::assertTrue(class_exists($id) || interface_exists($id));
            self::assertInstanceOf($id, $services->get($id));
        }
    }

    public function testThePackageRegistersItsServiceByInterfaceAndKeepsItPrivate(): void
    {
        $container = $this->loadContainer();
        self::assertTrue($container->hasDefinition(TeapotInterface::class));
        self::assertFalse($container->getDefinition(TeapotInterface::class)->isPublic());
        self::assertFalse($container->has(Teapot::class));
        self::assertFalse($container->getDefinition(ProjectTranslationResourceProvider::class)->isPublic());
        $services = $this->services($container);
        self::assertSame($services->get(TeapotInterface::class), $services->get(TeapotInterface::class));
    }

    public function testTheConfiguredTeapotBrewsTeaAndRemainsStringable(): void
    {
        $teapot = $this->services($this->loadContainer())->get(TeapotInterface::class);
        self::assertInstanceOf(TeapotInterface::class, $teapot);
        self::assertSame("I'm a teapot", (string) $teapot);
        self::assertSame('The tea is ready.', $teapot->brew('Tea'));
    }

    public function testTheTaggedProviderExposesThePackageCatalogue(): void
    {
        $container = $this->loadContainer();
        self::assertArrayHasKey(
            ProjectTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $provider = $this->services($container)->get(ProjectTranslationResourceProvider::class);
        self::assertInstanceOf(ProjectTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        self::assertCount(1, $directories);
        self::assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
        self::assertFileExists($directories[0] . '/errors+intl-icu.es.php');
    }

    public function testTheConsumersTranslatorUsesTheTaggedResourcesForARealError(): void
    {
        $services = $this->services($this->loadContainer());
        $translator = $services->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        $teapot = $services->get(TeapotInterface::class);
        self::assertInstanceOf(TeapotInterface::class, $teapot);
        try {
            $teapot->brew('coffee');
            self::fail('A teapot accepted a drink other than tea.');
        } catch (TranslatableInvalidArgumentException $exception) {
            self::assertSame('Una tetera no puede preparar coffee.', $exception->trans($translator));
        }
    }
}
