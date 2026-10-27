<?php

declare(strict_types=1);

/**
 * Derafu: Project - Template of a Derafu library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Project\Contract;

use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use Stringable;

/**
 * Contract of the example service included in the library template.
 */
interface TeapotInterface extends Stringable
{
    /**
     * Brews a drink. A teapot only brews tea.
     *
     * @param string $drink The drink to brew.
     * @return string What the teapot says when the drink is ready.
     * @throws TranslatableInvalidArgumentException If the drink is not tea.
     */
    public function brew(string $drink): string;
}
