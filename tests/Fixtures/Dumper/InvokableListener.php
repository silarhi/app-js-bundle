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

use Symfony\Component\Form\FormEvent;

final class InvokableListener
{
    public function __invoke(FormEvent $event): void
    {
    }

    public static function onPreSetData(FormEvent $event): void
    {
    }
}
