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
 * Dumps the public constants of a class as a TypeScript module (app-js:dump-constants): a limit shared by a constraint
 * and the code of the page, for instance. Its private and protected constants stay out, and so do the classes without
 * it.
 *
 * Found through autoconfiguration: the class must be loaded by a service resource of the application (the default
 * `App\` one loads `src/`), not excluded from it, or sit in a directory of the "constants.paths" option.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ConstantsToJs
{
    /**
     * @param string|null $name the name of the module and its file, the class's short name by default
     */
    public function __construct(public ?string $name = null)
    {
    }
}
