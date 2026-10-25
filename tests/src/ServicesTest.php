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

use Derafu\Project\Teapot;
use Derafu\Project\Translation\ProjectTranslationResourceProvider;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Imports the actual package configuration and checks its provider both as a
 * service and through the translator of a consuming application. Adapt the
 * construction checks and behavior examples when the library adds services.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function loadContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/resources/config')))
            ->load('project-services.yaml');

        return $container;
    }

    private function compile(ContainerBuilder $container): void
    {
        // Keep package services reachable so unused-private-service removal
        // cannot hide an invalid definition from the construction checks.
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }
        $container->compile();
    }

    private function consumerTranslator(): Translator
    {
        $container = $this->loadContainer();
        // The application chooses its own translator and locale. The package
        // only contributes its resource provider through the declared tag.
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')]);
        $this->compile($container);
        $translator = $container->get('app.translator');
        self::assertInstanceOf(Translator::class, $translator);

        return $translator;
    }

    public function testEveryConfiguredServiceCanBeConstructed(): void
    {
        $container = $this->loadContainer();
        $this->compile($container);
        self::assertInstanceOf(ProjectTranslationResourceProvider::class, $container->get(ProjectTranslationResourceProvider::class));
    }

    public function testTheTaggedProviderExposesThePackageCatalogue(): void
    {
        $container = $this->loadContainer();
        self::assertArrayHasKey(
            ProjectTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $this->compile($container);
        $provider = $container->get(ProjectTranslationResourceProvider::class);
        self::assertInstanceOf(ProjectTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        self::assertCount(1, $directories);
        self::assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
        self::assertFileExists($directories[0] . '/errors+intl-icu.es.php');
    }

    public function testTheConsumersTranslatorUsesTheTaggedResourcesForARealError(): void
    {
        $translator = $this->consumerTranslator();
        try {
            (new Teapot())->brew('coffee');
            self::fail('A teapot accepted a drink other than tea.');
        } catch (TranslatableInvalidArgumentException $exception) {
            self::assertSame('Una tetera no puede preparar coffee.', $exception->trans($translator));
        }
    }
}
