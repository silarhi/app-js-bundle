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

use Silarhi\AppJsBundle\Tests\Fixtures\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * "address" is a field in one variant and a form in the other.
 *
 * @extends AbstractType<mixed>
 */
final class SwitchingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('address', true === $options['detailed'] ? AddressType::class : TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('detailed')->allowedTypes('bool')->default(false);
    }
}
