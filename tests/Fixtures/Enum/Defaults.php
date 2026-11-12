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

namespace Silarhi\AppJsBundle\Tests\Fixtures\Enum;

use Silarhi\AppJsBundle\Attribute\EnumToJs;

/** Not an enum: skipped even when marked */
#[EnumToJs]
final class Defaults
{
    public const RATE = 20;
}
