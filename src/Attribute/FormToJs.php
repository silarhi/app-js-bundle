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

namespace Silarhi\AppJsBundle\Attribute;

use Attribute;
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;

/**
 * Dumps the shape of a form type for formView<Shape>() of @silarhi/app-js (app-js:dump-forms).
 *
 * The fields a variant does not render become optional. Variants are an array when their options are known at compile
 * time, or the class of a FormVariantProviderInterface service when they need data or other services:
 *
 *     #[FormToJs(variants: [
 *         'with web site' => ['options' => ['web_site' => true]],
 *         'without' => ['options' => ['web_site' => false]],
 *     ])]
 *     final class LandlordType extends AbstractType
 *
 *     #[FormToJs(variants: LeaseFormVariants::class)]
 *     final class LeaseType extends AbstractType
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FormToJs
{
    /**
     * @param string|null                                                                            $name     the name of the interface and its file, LandlordForm for LandlordType by default
     * @param class-string<FormVariantProviderInterface>|array<array-key, array<string, mixed>>|null $variants how to build the form, one build with the default options when null
     * @param bool                                                                                   $dynamic  false when the PRE_SET_DATA listeners of the form add no field
     */
    public function __construct(
        public ?string $name = null,
        public string|array|null $variants = null,
        public bool $dynamic = true,
    ) {
    }
}
