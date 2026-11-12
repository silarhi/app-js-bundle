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

namespace Silarhi\AppJsBundle\Form\Variant;

/**
 * One way of building a form for app-js:dump-forms: its options, and the data it is built with.
 */
final readonly class FormVariant
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public array $options = [],
        public mixed $data = null,
    ) {
    }
}
