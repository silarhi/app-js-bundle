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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * One field per element and value a core type renders.
 *
 * @extends AbstractType<mixed>
 */
final class ElementsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('body', TextareaType::class)
            ->add('kind', ChoiceType::class, ['choices' => ['A' => 'a']])
            ->add('roles', ChoiceType::class, ['choices' => ['A' => 'a'], 'multiple' => true])
            // The container of its options
            ->add('size', ChoiceType::class, ['choices' => ['S' => 's'], 'expanded' => true])
            ->add('tags', ChoiceType::class, ['choices' => ['A' => 'a'], 'expanded' => true, 'multiple' => true])
            ->add('agree', CheckboxType::class)
            ->add('yes', RadioType::class)
            ->add('amount', MoneyType::class)
            ->add('startsAt', DateTimeType::class, ['widget' => 'single_text'])
            // Compound: an embedded form of three selects
            ->add('birthday', BirthdayType::class, ['widget' => 'choice'])
            // No core type tells how it renders
            ->add('code', FormType::class, ['compound' => false])
            ->add('save', SubmitType::class);
    }
}
