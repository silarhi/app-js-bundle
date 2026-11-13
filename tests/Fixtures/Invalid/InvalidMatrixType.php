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

namespace Silarhi\AppJsBundle\Tests\Fixtures\Invalid;

use Silarhi\AppJsBundle\Attribute\FormToJs;
use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<mixed>
 */
#[FormToJs(matrix: ['with_siren' => []])]
final class InvalidMatrixType extends AbstractType
{
}
