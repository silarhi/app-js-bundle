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

namespace Silarhi\AppJsBundle\Form\Extension;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Builds an entity field without its choices, so the dump never queries the database: the shape of an entity is a
 * `string` whatever its choices. Registered only on the form registry of the dump.
 *
 * @internal
 */
final class EntityWithoutChoicesTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // EntityType loads its choices from the database only when none are given
        $resolver->setDefault('choices', []);
        // Its id reader loads the class metadata, which may connect to read the version of the platform
        $resolver->setNormalizer('id_reader', static fn (): null => null);
    }
}
