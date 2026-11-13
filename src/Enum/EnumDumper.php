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

namespace Silarhi\AppJsBundle\Enum;

use BackedEnum;

use function is_int;

use Silarhi\AppJsBundle\Writer\Format;
use Silarhi\AppJsBundle\Writer\TypeScript;

use function sprintf;
use function Symfony\Component\String\u;

use UnitEnum;

/**
 * One module per enum (TypeScript or JavaScript: the same code): a constant per case name, and the frozen cases as
 * the default export.
 *
 *     export const DRAFT_NAME = 'Draft'
 *
 *     const Status = Object.freeze({
 *         Draft: 'draft',
 *     })
 *     export default Status
 *
 * @internal
 */
final readonly class EnumDumper
{
    /**
     * @param array<string, class-string<UnitEnum>> $enums the enums marked #[EnumToJs], by module name
     */
    public function __construct(
        private array $enums,
        private Format $format = Format::TypeScript,
    ) {
    }

    /** The extension of the dumped files: ts or js. */
    public function getExtension(): string
    {
        return $this->format->value;
    }

    /**
     * @return array<string, string> the content of each file, by its path relative to the output directory
     */
    public function getFiles(): array
    {
        $files = [];
        foreach ($this->enums as $name => $enum) {
            $cases = $enum::cases();
            if ([] === $cases) {
                continue;
            }

            $files[$name . '.' . $this->getExtension()] = $this->render($name, $cases);
        }

        return $files;
    }

    /**
     * @param non-empty-list<UnitEnum> $cases
     */
    private function render(string $name, array $cases): string
    {
        $constants = [];
        $properties = [];
        foreach ($cases as $case) {
            $value = $case instanceof BackedEnum ? $case->value : $case->name;

            $constants[] = sprintf('export const %s_NAME = %s', u($case->name)->snake()->upper()->toString(), TypeScript::quote($case->name));
            $properties[] = sprintf('    %s: %s,', $case->name, is_int($value) ? (string) $value : TypeScript::quote($value));
        }

        return implode("\n", $constants) . "\n\n"
            . sprintf("const %s = Object.freeze({\n%s\n})\n", $name, implode("\n", $properties))
            . sprintf("export default %s\n", $name);
    }
}
