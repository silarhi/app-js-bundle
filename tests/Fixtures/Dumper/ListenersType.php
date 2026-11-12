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
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * PRE_SET_DATA listeners of every kind: a method, an invokable object, a static method, on the root, a child and the
 * prototype of a collection.
 *
 * @extends AbstractType<mixed>
 */
final class ListenersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add($builder->create('child', FormType::class)->addEventListener(FormEvents::PRE_SET_DATA, new InvokableListener()))
            ->add('items', CollectionType::class, [
                'entry_type' => StaticListenerType::class,
                'allow_add' => true,
            ])
            ->add('name', TextType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, $this->onPreSetData(...))
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
    }
}
