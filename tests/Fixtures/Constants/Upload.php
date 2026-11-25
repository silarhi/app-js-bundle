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
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;

#[ConstantsToJs]
/** Not final: its non-public constants stay protected */
class Upload
{
    public const MAX_SIZE = 2_000_000;
    public const RATIO = 16 / 9;
    public const LABEL = "it's";
    public const ENABLED = true;
    public const NOTHING = null;
    public const MIME_TYPES = ['image/jpeg', 'image/png'];
    public const SIZES = ['2:1' => [1200, 600], 'tall' => [900, 1200]];
    public const EMPTY = [];
    public const STATUS = Status::Draft;
    public const KIND = Kind::Text;
    public const LONG = ['jpeg-image-format', 'png-image-format', 'gif-image-format', 'webp-image-format', 'avif-image-format'];
    protected const HIDDEN = 1;
}
