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

namespace Silarhi\AppJsBundle\Form;

use Symfony\Component\Form\FormInterface;

/**
 * The errors of a submitted form, keyed by the dot paths formView() of @silarhi/app-js reads: `phones.0.number`, and
 * `''` for the errors of the form itself.
 */
final class FormViolations
{
    /**
     * @param FormInterface<mixed> $form
     *
     * @return list<array{path: string, message: string}> in rendering order
     */
    public static function collect(FormInterface $form): array
    {
        $violations = [];
        self::collectForm($form, '', $violations);

        return $violations;
    }

    /**
     * @param FormInterface<mixed>                       $form
     * @param list<array{path: string, message: string}> $violations
     */
    private static function collectForm(FormInterface $form, string $path, array &$violations): void
    {
        foreach ($form->getErrors() as $error) {
            $violations[] = ['path' => $path, 'message' => $error->getMessage()];
        }

        foreach ($form as $name => $child) {
            self::collectForm($child, ('' === $path ? '' : $path . '.') . $name, $violations);
        }
    }
}
