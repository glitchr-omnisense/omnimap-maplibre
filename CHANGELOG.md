# Changelog

Every version of omnimap/maplibre, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)). A release's notes are its section here: the release
workflow refuses a tag without one.

## Unreleased

### Fixed
- No key of a style's URL (MapTiler's `?key=`) in what `Style::fetch()` throws: the address in an
  `UnreachableException`'s or a `ProviderException`'s message is masked (`key=***`), the
  previous is a copy of the client's exception (the same class and code), and no trace of the chain
  keeps its arguments. Needs omnisense/omnimap's `Http\Redaction`.

### Changed
- Requires `omnisense/omnimap`, the core's new name (Omniscient is now Omnisense); the repository is `glitchr-omnisense/omnimap-maplibre`.

## [0.1.0] - 2026-10-25

### Added
- `MapLibreFactory` (`maplibre`) and `MapLibreProvider`: a renderer for omniscient/omnimap drawing
  a MapLibre vector style in the browser - OpenFreeMap's "liberty" by default, no key, no cookie -
  with MapLibre GL JS 5.24.0 from unpkg under SHA-384 integrity, or one's own copy, or MapLibre 6 as
  an ES module.
- `assets/maplibre.js`: the driver for omnimap's script (markers with their labels, navigation
  controls, the drawing buffer kept for `capture.js`).
- `Style`: a style read on the server, its sources' TileJSON followed, and the hosts it makes the
  browser reach.
- omnimap's conformance suite (`Tests/MapLibreContractTest.php`), live checks against OpenFreeMap
  and unpkg (`--group live`), `BareTest`.
