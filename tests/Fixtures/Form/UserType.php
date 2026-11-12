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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
#[FormToJs(variants: [
    'company' => ['options' => ['with_siren' => true]],
    'person' => ['options' => ['with_siren' => false]],
])]
final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('size', ChoiceType::class, ['choices' => ['S' => 's', 'M' => 'm'], 'expanded' => true])
            ->add('tags', ChoiceType::class, ['choices' => ['A' => 'a'], 'expanded' => true, 'multiple' => true])
            ->add('address', AddressType::class)
            ->add('phones', CollectionType::class, ['entry_type' => PhoneType::class, 'allow_add' => true])
            ->add('notes', CollectionType::class, ['entry_type' => TextType::class, 'allow_add' => true])
            ->add('emails', CollectionType::class, ['entry_type' => TextType::class])
            ->add('start-date', TextType::class);

        if (true === $options['with_siren']) {
            $builder->add('siren', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('with_siren')
            ->allowedTypes('bool')
            ->default(false);
    }
}
