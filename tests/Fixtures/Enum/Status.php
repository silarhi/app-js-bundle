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
use Symfony\Component\Translation\TranslatableMessage;

#[EnumToJs(methods: ['label', 'isFinal'])]
enum Status: string
{
    case Draft = 'draft';
    case EInvoice = 'e_invoice';
    case Quoted = "it's";

    public function label(): TranslatableMessage
    {
        return new TranslatableMessage('status.' . $this->name);
    }

    public function isFinal(): bool
    {
        return self::Draft !== $this;
    }

    public function describe(string $locale): string
    {
        return $locale . ':' . $this->value;
    }

    public static function getDefault(): self
    {
        return self::Draft;
    }
}
