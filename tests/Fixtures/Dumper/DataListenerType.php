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

use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A type that cannot be built without data, as one built for its entity's pages only: its buildForm() and its
 * PRE_SET_DATA listener read it unguarded.
 *
 * @extends AbstractType<mixed>
 */
final class DataListenerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['data'] instanceof Item) {
            throw new LogicException('An item is needed.');
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            if (!$event->getData() instanceof Item) {
                throw new LogicException('An item is needed.');
            }

            $event->getForm()->add('name', TextType::class);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Item::class);
    }
}
