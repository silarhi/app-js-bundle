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

namespace Silarhi\AppJsBundle\Attribute;

use Attribute;

/**
 * Dumps an enum as a TypeScript module (app-js:dump-enums). The enums without it are not dumped.
 *
 * Found through autoconfiguration: the enum must be loaded by a service resource of the application (the default
 * `App\` one loads `src/Enum`), not excluded from it.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class EnumToJs
{
    /**
     * @param string|null $name the name of the module and its file, the enum's short name by default
     */
    public function __construct(public ?string $name = null)
    {
    }
}
