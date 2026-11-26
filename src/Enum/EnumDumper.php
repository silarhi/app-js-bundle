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
use InvalidArgumentException;

use function is_int;

use LogicException;
use ReflectionMethod;
use Silarhi\AppJsBundle\Attribute\EnumToJs;
use Silarhi\AppJsBundle\Finder\MarkedClassFinder;
use Silarhi\AppJsBundle\Writer\Format;
use Silarhi\AppJsBundle\Writer\Literal;
use Silarhi\AppJsBundle\Writer\TypeScript;

use function sprintf;
use function strlen;
use function Symfony\Component\String\u;

use UnitEnum;

/**
 * One module per enum (TypeScript or JavaScript: the same code, the type as JSDoc): a constant per case name, the
 * frozen cases as the default export, the type of their values, and the result of each dumped method by case value.
 *
 *     export const DRAFT_NAME = 'Draft'
 *
 *     const Status = Object.freeze({
 *         Draft: 'draft',
 *     })
 *     export default Status
 *
 *     export type StatusValue = (typeof Status)[keyof typeof Status]
 *
 *     export const LABEL = Object.freeze({
 *         draft: 'status.draft',
 *     })
 */
final readonly class EnumDumper
{
    /** Biome's line width: a longer declaration is broken after its `=`, as Biome breaks it */
    private const LINE_WIDTH = 120;

    /**
     * @param array<string, class-string<UnitEnum>> $enums   the enums marked #[EnumToJs], by module name
     * @param array<string, list<string>>           $methods the methods to dump the results of, by module name
     */
    public function __construct(
        private array $enums,
        private Format $format = Format::TypeScript,
        private array $methods = [],
    ) {
    }

    /**
     * The dumper of the enums marked #[EnumToJs] in directories: what the bundle finds, for an application without it.
     *
     * @param array<string, string> $paths the directories, with their namespace prefix (`['/app/src/Enum' => 'App\\Enum']`)
     *
     * @throws InvalidArgumentException when two enums have the same module name, or a dumped method cannot be called
     */
    public static function fromDirectories(array $paths, Format $format = Format::TypeScript): self
    {
        $collector = new EnumCollector();
        foreach (MarkedClassFinder::find($paths, EnumToJs::class) as [$enum, $attribute]) {
            if ($enum->isEnum()) {
                $collector->add($enum, $attribute->name);
            }
        }

        return new self($collector->getEnums(), $format, $collector->getMethods());
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

            $files[$name . '.' . $this->getExtension()] = $this->render($name, $cases, $this->methods[$name] ?? []);
        }

        return $files;
    }

    /**
     * @param non-empty-list<UnitEnum> $cases
     * @param list<string>             $methods
     */
    private function render(string $name, array $cases, array $methods): string
    {
        $exports = [];
        $constants = [];
        $properties = [];
        foreach ($cases as $case) {
            $constant = u($case->name)->snake()->upper()->toString() . '_NAME';
            $exports[$constant] = $case->name;
            $constants[] = $this->declaration('export const ' . $constant, TypeScript::quote($case->name));
            $properties[] = sprintf('    %s: %s,', $case->name, $this->renderValue($case));
        }

        $type = $name . 'Value';
        $code = implode("\n", $constants) . "\n\n"
            . sprintf("const %s = Object.freeze({\n%s\n})\n", $name, implode("\n", $properties))
            . sprintf("export default %s\n\n", $name)
            . (Format::JavaScript === $this->format
                ? sprintf("/** @typedef {(typeof %s)[keyof typeof %s]} %s */\n", $name, $name, $type)
                : $this->declaration('export type ' . $type, sprintf('(typeof %s)[keyof typeof %s]', $name, $name)) . "\n");

        foreach ($methods as $method) {
            $constant = u($method)->snake()->upper()->toString();
            if (isset($exports[$constant]) || $constant === $name || $constant === $type) {
                throw new LogicException(sprintf('Unable to dump the method "%s()" of the enum "%s": its export "%s" is already taken.', $method, $name, $constant));
            }

            $exports[$constant] = $method;
            $code .= sprintf("\nexport const %s = %s\n", $constant, $this->renderResults($cases, $method));
        }

        return $code;
    }

    /**
     * `export const X = …` or `export type X = …` on one line when it fits, else broken after its `=` with the value
     * indented on the next line, even when the value alone is still too long: Biome breaks a string or a type there.
     */
    private function declaration(string $declaration, string $value): string
    {
        $line = $declaration . ' = ' . $value;

        return strlen($line) <= self::LINE_WIDTH ? $line : $declaration . " =\n    " . $value;
    }

    private function renderValue(UnitEnum $case): string
    {
        $value = $this->getValue($case);

        return is_int($value) ? (string) $value : TypeScript::quote($value);
    }

    private function getValue(UnitEnum $case): int|string
    {
        return $case instanceof BackedEnum ? $case->value : $case->name;
    }

    /**
     * The result of a method for each case, by case value.
     *
     * @param non-empty-list<UnitEnum> $cases
     */
    private function renderResults(array $cases, string $method): string
    {
        $results = [];
        foreach ($cases as $case) {
            $key = TypeScript::key((string) $this->getValue($case));

            try {
                // An object written by hand, never a list: its keys are the case values, 0 and 1 included
                $results[] = sprintf('    %s: %s,', $key, Literal::render((new ReflectionMethod($case, $method))->invoke($case), strlen($key) + 6, 1));
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException(sprintf('The result of %s::%s->%s() cannot be dumped: %s', $case::class, $case->name, $method, $exception->getMessage()), $exception->getCode(), $exception);
            }
        }

        return sprintf("Object.freeze({\n%s\n})", implode("\n", $results));
    }
}
