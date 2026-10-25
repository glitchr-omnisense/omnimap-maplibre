# MapLibre

## Installation

```sh
composer require omnimap/maplibre
```

Then `assets/maplibre.js` beside omnimap's `assets/omnimap.js` in the page (see the README).

## Options

| Option | Default | Meaning |
|---|---|---|
| `style` | `https://tiles.openfreemap.org/styles/liberty` | The MapLibre style's URL. OpenFreeMap also serves `bright` and `positron`. |
| `attribution` | OpenFreeMap, OpenMapTiles, OpenStreetMap | HTML shown beside a kept picture of the map; the live map shows its sources' own. |
| `hosts` | the style's host | The hosts the browser reaches for the style, its tiles, glyphs and sprite (a list, or comma separated). |
| `library_script`, `library_script_integrity` | unpkg, MapLibre GL JS 5.24.0, its SHA-384 | The script the browser loads. Serve your own copy to keep visitors off a CDN. |
| `library_style`, `library_style_integrity` | the same, its stylesheet | |
| `library_module` | none | An ES module instead of the script (MapLibre GL JS 6 is published as modules only). No integrity check covers what a module imports. |
| `preserve_drawing_buffer` | `true` | Keeps the canvas readable for `assets/capture.js`. |

A view's own MapLibre options: `$view->withOptions('maplibre', ['map' => ['pitch' => 45]])` -
handed to `new maplibregl.Map()` as they are, and part of the picture's fingerprint.

## The style's hosts

The browser reaches more than the style's URL: its sources' TileJSON and tiles, its glyphs, its
sprite. A consent banner and a Content-Security-Policy must name them all. Read them from the style
on the server:

```php
$provider = (new MapLibreFactory($httpClient))->create(['style' => $myStyle]);
$hosts = $provider->inspect()->hosts();   // e.g. ['tiles.openfreemap.org']
```

and give them as `hosts` when they are not all on the style's host:
`capabilities()->browserHosts` is what omnimap's consent gate and a host's CSP read.

## What it answers

`MapRendererInterface` only. `Registry::staticMap()`, `geocoder()` and `tiles()` refuse it with
`NotSupportedException`: pair it with omnimap/openstreetmap for images made on the server or for
geocoding.

## Errors

`Style::fetch()` / `inspect()`: an answer other than 200, or a body that is not a style
(version 8 and its sources) or not JSON, is a `ProviderException`; a request that got no answer an
`UnreachableException`; `inspect()` without the factory's HTTP client a `NotSupportedException`.

## Terms

OpenFreeMap's terms (https://openfreemap.org/tos/), the OpenMapTiles licence and the OpenStreetMap
copyright (attribution shown on the map). OpenFreeMap needs no key and sets no cookie; another
style's provider may need both.
