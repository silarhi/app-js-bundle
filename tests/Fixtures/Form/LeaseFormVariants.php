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

namespace Silarhi\AppJsBundle\Tests\Fixtures\Form;

use Override;
use Silarhi\AppJsBundle\Form\Variant\FormVariant;
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;

final class LeaseFormVariants implements FormVariantProviderInterface
{
    #[Override]
    public function getVariants(): iterable
    {
        yield 'furnished' => new FormVariant(data: ['furnished' => true]);
        yield 'unfurnished' => new FormVariant(data: ['furnished' => false]);
    }
}
