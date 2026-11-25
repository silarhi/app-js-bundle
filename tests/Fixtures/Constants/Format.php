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

namespace Silarhi\AppJsBundle\Tests\Fixtures\Constants;

use Silarhi\AppJsBundle\Attribute\ConstantsToJs;

/** Its cases are dumped by app-js:dump-enums: only its constant here */
#[ConstantsToJs]
enum Format: string
{
    public const MAX_PAGES = 24;

    case Grid = 'grid';
    case List = 'list';
}
