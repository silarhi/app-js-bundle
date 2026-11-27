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

namespace Silarhi\AppJsBundle\Tests\Fixtures\ClosureConstants;

use Silarhi\AppJsBundle\Attribute\ConstantsToJs;

/**
 * A closure in a constant needs PHP 8.5: kept out of the Constants fixtures, which the test kernel loads on every version.
 */
#[ConstantsToJs]
final class Callbacks
{
    public const MAX_LENGTH = 80;
    public const NORMALIZE = trim(...);
}
