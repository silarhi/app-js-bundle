# Changelog

All notable changes to `silarhi/app-js-bundle` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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

[0.1.0]: https://github.com/silarhi/app-js-bundle/releases/tag/v0.1.0
