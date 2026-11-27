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

use function array_slice;

use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The values each kind of choice is read as.
 *
 * @extends AbstractType<mixed>
 */
final class ChoicesType extends AbstractType
{
    private const LONG = ['alpha_one', 'beta_two', 'gamma_three', 'delta_four', 'epsilon_five', 'zeta_six', 'eta_seven', 'theta_eight'];

    private const MIDDLE = ['draft', 'active', 'terminated', 'suspended', 'archived', 'pending_validation'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, ['class' => Status::class])
            // Not required: its placeholder option reads ''
            ->add('optional', ChoiceType::class, ['choices' => ['A' => 'a', 'B' => 'b'], 'required' => false])
            ->add('grouped', ChoiceType::class, ['choices' => ['Group' => ['A' => 'a', 'B' => 'b'], 'C' => 'c'], 'preferred_choices' => ['c']])
            ->add('radios', ChoiceType::class, ['choices' => ['X' => 'x', 'Y' => 'y'], 'expanded' => true, 'required' => false])
            ->add('owner', FakeEntityType::class, ['choices' => ['Ann' => '1']])
            // No choice: its values are not typed
            ->add('none', ChoiceType::class, ['choices' => []])
            ->add('level', ChoiceType::class, ['choices' => true === $options['extended'] ? ['1' => '1', '3' => '3'] : ['1' => '1', '2' => '2']])
            // Its union fits on a line of its own
            ->add('middle', ChoiceType::class, ['choices' => array_combine(self::MIDDLE, self::MIDDLE)])
            ->add('longOne', ChoiceType::class, ['choices' => array_combine(self::LONG, self::LONG)])
            ->add('longMany', ChoiceType::class, ['choices' => array_combine(self::LONG, self::LONG), 'multiple' => true])
            ->add('longItems', CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => ['choices' => array_combine(array_slice(self::LONG, 0, 5), array_slice(self::LONG, 0, 5))],
                'allow_add' => true,
            ])
            // Its item breaks too
            ->add('longerItems', CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => ['choices' => array_combine(self::LONG, self::LONG)],
                'allow_add' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('extended')->allowedTypes('bool')->default(false);
    }
}
