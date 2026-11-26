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

namespace Silarhi\AppJsBundle\Tests\Fixtures\Dumper;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Rendered as Doctrine's EntityType is, whose values are ids from the database.
 *
 * @extends AbstractType<mixed>
 */
final class FakeEntityType extends AbstractType
{
    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'entity';
    }
}
