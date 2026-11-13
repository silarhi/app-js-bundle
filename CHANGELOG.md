# Changelog

All notable changes to `silarhi/app-js-bundle` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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

[0.3.0]: https://github.com/silarhi/app-js-bundle/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/silarhi/app-js-bundle/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/silarhi/app-js-bundle/releases/tag/v0.1.0
