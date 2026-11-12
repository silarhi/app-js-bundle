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

use function is_array;

use Silarhi\AppJsBundle\Attribute\FormToJs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @extends AbstractType<mixed>
 */
#[FormToJs(name: 'Lease', variants: LeaseFormVariants::class)]
final class LeaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reference', TextType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();
            if (is_array($data) && true === ($data['furnished'] ?? false)) {
                $event->getForm()->add('inventory', TextType::class);
            }
        });
    }
}
