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
 * The form is built once per combination of the values of the matrix, with the options. A field one build does not
 * render becomes optional:
 *
 *     #[FormToJs(options: ['profile' => Profile::Landlord], matrix: ['with_siren' => [true, false]])]
 *     final class LandlordType extends AbstractType
 *
 * Builds that need data, services, or options that go together come from a FormVariantProviderInterface service,
 * built with the options and the matrix too:
 *
 *     #[FormToJs(variants: LeaseFormVariants::class)]
 *     final class LeaseType extends AbstractType
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FormToJs
{
    /**
     * @param string|null                                     $name     the name of the interface and its file, LandlordForm for LandlordType by default
     * @param array<string, mixed>                            $options  the options of every build
     * @param array<string, list<mixed>>                      $matrix   the values of each option to build the form with, one build per combination
     * @param class-string<FormVariantProviderInterface>|null $variants the service that gives the builds that need data or services
     * @param bool                                            $dynamic  false when the PRE_SET_DATA listeners of the form add no field
     */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public array $matrix = [],
        public ?string $variants = null,
        public bool $dynamic = true,
    ) {
    }
}
