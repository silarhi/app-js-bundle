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

namespace Silarhi\AppJsBundle\Form;

/**
 * @internal
 */
final readonly class FormShapeDump
{
    /**
     * @param array<string, string> $files    the content of each file, by its path relative to the output directory
     * @param list<string>          $warnings the forms whose shape may miss fields
     */
    public function __construct(
        public array $files,
        public array $warnings,
    ) {
    }
}
