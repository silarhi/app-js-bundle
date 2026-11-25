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

namespace Silarhi\AppJsBundle\Tests\Fixtures\TypedConstants;

use Silarhi\AppJsBundle\Attribute\ConstantsToJs;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;

/**
 * Typed class constants need PHP 8.3: kept out of the Constants fixtures, which the test kernel loads on every version.
 */
#[ConstantsToJs]
final class Typed
{
    public const int MAX_SIZE = 2_000_000;
    public const ?string NOTHING = null;
    public const int|float RATIO = 16 / 9;
    public const array SIZES = ['tall' => [900, 1200]];
    public const Status STATUS = Status::Draft;
}
