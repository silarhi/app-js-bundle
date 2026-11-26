# Changelog

All notable changes to `silarhi/app-js-bundle` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.10.0] - Unreleased

### Added

- `app-js:dump-forms` types the value of a choice as the union of its options: `FormField<HTMLSelectElement, 'draft' |
'active' | null>` for an enum or a list of choices, `('a' | 'b')[]` when multiple, `''` included when a placeholder
  option is rendered. The variants' options are merged. Entities, the Intl lists and the selects of a date or a time
  stay `string`. A long field type is broken over lines as Biome breaks it.
- `app-js:dump-enums` exports the type of the values (`export type StatusValue = (typeof Status)[keyof typeof
Status]`, a JSDoc `@typedef` for `format: js`), and `#[EnumToJs(methods: ['label', 'color'])]` dumps the results of
  those methods by case value (`export const LABEL = Object.freeze({ draft: 'status.draft' })`), a translatable
  message as its key. A method that is not public, is static or takes a required parameter fails the container build.
- `load_page()` passes a form view as its id, a date in ISO 8601, an enum case as its value (its name for a unit enum),
  and translates a translatable message, at any depth of the params.
- `FormViolations::collect($form)` and `FormViolationsResponse` (a 422 JSON response): the errors of a form keyed by
  the paths `formView()` reads (`phones.0.number`, `''` for the form itself), for `postForm()` of
  `@silarhi/app-js/http`.
- `app-js:dump` runs every dump, with `--check` and `--strict`.
- `--check -v` shows the diff of each file out of date.

### Changed

- The dumped form shapes of choices with typed values change: run `app-js:dump-forms` once, and a page that compared a
  choice with a value it does not have no longer compiles.
- `load_page('page', {})` renders `data-page-params="{}"` instead of `[]`, as without params.

## [0.9.0] - 2026-11-25

### Added

- `app-js:dump-constants` dumps the public constants of the classes marked `#[ConstantsToJs]` as TypeScript or
  JavaScript modules (`export const MAX_SIZE = 2000000`), so a limit shared by a constraint and a page lives in one
  place. Scalars, `null`, enum cases and arrays of them (frozen); `#[ConstantsToJs(name: ...)]` renames the module,
  and the `constants` option sets `output_dir`, `format` and the `paths` scanned for the classes no resource loads.
  `--check` fails when a file is out of date.

## [0.8.0] - 2026-11-19

### Added

- `#[FormToJs(optional: [...])]` reads the fields at the given dot paths as optional (`sharedAccount?:`), for a field
  every build has but the template does not always render: `optional: ['sharedAccount', 'address.country',
'phones.{index}.fax']`. A path that matches no field of the builds fails the dump.

## [0.7.0] - 2026-11-13

### Added

- `#[FormToJs(options: [...])]` gives the options of every build, and `#[FormToJs(matrix: [...])]` the values each
  option takes: the form is built once per combination, with the options (`matrix: ['with_bank' => [false, true]]`).
  With a `variants` provider too, each of its variants is built with each combination. A matrix stops at 64 builds.

### Changed

- **Breaking**: `#[FormToJs(variants: ...)]` only takes the class of a `FormVariantProviderInterface` service. An array
  of variants becomes `options` and a `matrix`: `['default' => ['options' => ['profile' => X]]]` is
  `options: ['profile' => X]`, and one variant per value of an option is `matrix: ['option' => [a, b]]`. Variants with
  data, or with options that only go together, come from a provider.

## [0.6.0] - 2026-11-13

### Added

- `app-js:dump-forms` builds a form type without data (no variant, or a variant that gives none) with its empty data, as
  Symfony computes it for a form submitted empty: a new instance of its `data_class` by default, or what its own
  `empty_data` gives, when that is an object. It is computed from the resolved options, so `buildForm()` and the
  `PRE_SET_DATA` listeners never run without data. A type that reads `$options['data']`, or whose listeners add fields,
  needs no variant provider for an empty entity, and no longer gets the warning about its listeners. An `empty_data`
  that fails keeps the build without data, named with `-v`.

## [0.5.0] - 2026-11-13

### Added

- `app-js:dump-forms` names the value each field is read as: `FormField<HTMLSelectElement, string[]>` for a multiple
  choice, `string | null` for a single one, `string` or `string[]` for an expanded one, `boolean` for a checkbox,
  `string` for the other core types rendered as an input, so `form.field('tags').value` and the `FormDefinition`
  conditions are typed. The dumped shapes need `@silarhi/app-js` 0.9.0+.

## [0.4.0] - 2026-11-13

### Added

- `app-js:dump-forms` names the element each field is rendered as: `FormField<HTMLSelectElement>` for a choice,
  `HTMLTextAreaElement`, `HTMLButtonElement`, `HTMLInputElement` for the other core types rendered as an input, so
  `form.get('country').element` is typed. An expanded choice, or a type no core type tells about, stays a `FormField`.
  The dumped shapes need `@silarhi/app-js` 0.8.0+.

## [0.3.1] - 2026-11-13

### Fixed

- `app-js:dump-forms` dumps a form type with a button (`SubmitType`, `ButtonType`), in the form or in the prototype of a
  collection, instead of failing on "Buttons do not support event dispatching". A button is a field of the shape.

## [0.3.0] - 2026-11-13

### Changed

- On Symfony 7.3+, the enums marked `#[EnumToJs]` are found through autoconfiguration, wherever they live in the
  application's service resources (the default `App\` resource loads `src/`).
- `enums.paths` is empty by default and only needed on Symfony 6.4 to 7.2, which registers no enum in the container:
  set it to `{'%kernel.project_dir%/src/Enum': 'App\Enum'}` there to keep the former default.
- An enum module is named after the enum (or `#[EnumToJs(name: ...)]`), at the root of `enums.output_dir`. A class
  marked `#[EnumToJs]` that is not an enum, or two enums with the same name, fail the container build.

## [0.2.0] - 2026-11-13

### Added

- An embedded form whose type is marked `#[FormToJs]` too, with the same shape, is a reference to its interface
  (`address: AddressForm`, `phones: FormCollection<PhoneForm>`), imported from its file.

## [0.1.0] - 2026-11-12

### Added

- `load_page()` Twig function, for the `data-page` listener of `@silarhi/app-js`.
- `app-js:dump-enums`: the enums marked `#[EnumToJs]` as TypeScript or JavaScript modules, formatted as Biome formats
  them.
- `app-js:dump-forms`: the shape of the form types marked `#[FormToJs]`, for `formView<Shape>()` of
  `@silarhi/app-js/form`. Variants (an array, or a `FormVariantProviderInterface` service) make the fields they do not
  render optional; a warning names the form types whose `PRE_SET_DATA` listeners may add fields.
- A `format` option per dumper: `ts`, or `js` (`.js` enum modules, `.d.ts` form shapes for JSDoc).
- `--check` on both commands, and `--strict` on `app-js:dump-forms`, for the CI.

[0.10.0]: https://github.com/silarhi/app-js-bundle/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/silarhi/app-js-bundle/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/silarhi/app-js-bundle/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/silarhi/app-js-bundle/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/silarhi/app-js-bundle/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/silarhi/app-js-bundle/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/silarhi/app-js-bundle/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/silarhi/app-js-bundle/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/silarhi/app-js-bundle/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/silarhi/app-js-bundle/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/silarhi/app-js-bundle/releases/tag/v0.1.0
