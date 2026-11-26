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

use Rector\Config\RectorConfig;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersAndFunctionsToAsTwigAttributeRector;

return RectorConfig::configure()
    ->withPaths([
        'src',
        'tests',
    ])
    // Their listeners are callables of every kind on purpose: no first-class callable conversion, no dead code removal
    ->withSkip([
        __DIR__ . '/tests/Fixtures/Dumper',
        // The bundle tags its Twig extension twig.extension itself, and #[AsTwigFunction] needs Symfony 7.3+
        GetFiltersAndFunctionsToAsTwigAttributeRector::class,
    ])
    ->reportUnusedSkips()
    ->withPhpSets()
    ->withComposerBased()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    );
