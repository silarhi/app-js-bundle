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

use Silarhi\AppJsBundle\Attribute\FormToJs;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\NamedItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Its data class needs a constructor argument: its empty data fails, the dump builds it without data and notes it.
 *
 * @extends AbstractType<mixed>
 */
#[FormToJs]
final class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', NamedItem::class);
    }
}
