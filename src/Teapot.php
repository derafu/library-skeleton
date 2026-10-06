<?php

declare(strict_types=1);

/**
 * Derafu: Project - Template of a Derafu library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Project;

use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Stringable;

/**
 * Represents a teapot.
 *
 * It is the example class of the template, with what every class of a Derafu
 * library has: a test that covers it and an exception whose message is
 * translatable. Delete it when the library has code of its own.
 */
final class Teapot implements Stringable
{
    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return "I'm a teapot";
    }

    /**
     * Brews a drink.
     *
     * @param string $drink The drink. A teapot only brews tea.
     * @return string What the teapot says when the drink is ready.
     * @throws InvalidArgumentException If the drink is not tea.
     */
    public function brew(string $drink): string
    {
        if (strtolower($drink) !== 'tea') {
            throw new InvalidArgumentException([
                'A teapot can not brew {drink}.',
                'drink' => $drink,
            ]);
        }

        return 'The tea is ready.';
    }
}
