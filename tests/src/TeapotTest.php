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
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test for the Teapot class.
 *
 * It is the example test of the template.
 */
#[CoversClass(Teapot::class)]
final class TeapotTest extends TestCase
{
    public function testItIsATeapot(): void
    {
        $this->assertSame("I'm a teapot", (string) (new Teapot()));
    }

    public function testItBrewsTea(): void
    {
        $this->assertSame('The tea is ready.', (new Teapot())->brew('Tea'));
    }

    public function testItDoesNotBrewAnythingElseWithATranslatableError(): void
    {
        try {
            (new Teapot())->brew('coffee');
            $this->fail('It should have failed.');
        } catch (InvalidArgumentException $e) {
            // A translatable error that is still the exception of PHP.
            $this->assertInstanceOf(TranslatableInterface::class, $e);
            $this->assertSame('A teapot can not brew coffee.', $e->getMessage());
        }
    }
}
