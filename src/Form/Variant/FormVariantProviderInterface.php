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
 * The variants of a form type, for #[FormToJs(variants: MyProvider::class)]. The output of app-js:dump-forms must
 * not depend on the machine: build the data in memory, never read it from a database.
 */
interface FormVariantProviderInterface
{
    /**
     * @return iterable<array-key, FormVariant> the variants, keyed by a name for the error messages
     */
    public function getVariants(): iterable;
}
