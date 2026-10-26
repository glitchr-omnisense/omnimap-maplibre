# omnimap/maplibre

MapLibre for [omnisense/omnimap](https://github.com/glitchr-omnisense/omnimap): the live map drawn in
the browser by [MapLibre GL JS](https://maplibre.org) from a vector style (`assets/maplibre.js`, a
driver for omnimap's `omnimap.js`) - by default [OpenFreeMap](https://openfreemap.org)'s
"liberty", free, without a key and without cookies - and the style read on the server
(`Omnimap\MapLibre\Style`) to name every host it makes a visitor's browser reach.

| Operation | Where | Address (defaults) |
|---|---|---|
| live map (browser) | MapLibre GL JS 5.24.0 from unpkg, then the style, its TileJSON, tiles, glyphs and sprite | `https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js` and `.css`; `https://tiles.openfreemap.org/styles/liberty` |
| style read (server, `inspect()`) | the style JSON and each source's TileJSON | `GET https://tiles.openfreemap.org/styles/liberty`, `GET https://tiles.openfreemap.org/planet` |

A renderer only: vector tiles are drawn by the browser's WebGL, so the server makes no image of a
view and serves no raster tiles. The picture of a view still reaches omnimap's map cache, captured
in the browser (`assets/capture.js`): the map keeps its drawing buffer for it.

## Installation

```sh
composer require omnimap/maplibre
```

Requires PHP 8.2, `omnisense/omnimap` (1.x) and `symfony/http-client-contracts`; reading a style on
the server needs a Symfony HTTP client (`symfony/http-client`). Then `assets/maplibre.js` beside
omnimap's `assets/omnimap.js` in the page:

```js
import { register, mountAll } from '.../omnimap/assets/omnimap.js';
import maplibre from '.../omnimap/maplibre/assets/maplibre.js';
register('maplibre', maplibre);
mountAll();
```

> [!WARNING]
> Known defect: omnibridge/symfony and omnibridge/laravel do not register `MapLibreFactory`
> (they list Google's and OpenStreetMap's factories only), where every installed omnimap provider
> is expected to be wired: `provider: maplibre` fails until the application registers the factory
> itself (Symfony: a service tagged `omnimap.provider_factory`; Laravel: its own `Registry`
> binding), as omnimap's Symfony and Laravel pages show.

## Credentials

None with OpenFreeMap: no key, no account, no cookie. Another style's provider may need a key,
which goes in the `style` URL (and is then seen by the visitor's browser).

## Options

`new MapLibreFactory(?HttpClientInterface $http = null)`; no option is required. The HTTP client
is only for `inspect()`; drawing needs none.

| Option | Type | Default | Meaning |
|---|---|---|---|
| `style` | string | `https://tiles.openfreemap.org/styles/liberty` (`MapLibreFactory::OPENFREEMAP_STYLE`) | The MapLibre style's URL. OpenFreeMap also serves `bright` and `positron`. |
| `attribution` | string (HTML) | OpenFreeMap, OpenMapTiles, OpenStreetMap (`OPENFREEMAP_ATTRIBUTION`) | HTML shown beside a kept picture of the map; the live map shows its sources' own. |
| `hosts` | list, or comma-separated string | `[]`: the style's host | The hosts the browser reaches for the style, its tiles, glyphs and sprite. |
| `library_script`, `library_script_integrity` | string | unpkg, MapLibre GL JS 5.24.0, its SHA-384 (`LIBRARY_SCRIPT`, `LIBRARY_SCRIPT_INTEGRITY`) | The script the browser loads. Serve your own copy to keep visitors off a CDN. |
| `library_style`, `library_style_integrity` | string | the same, its stylesheet (`LIBRARY_STYLE`, `LIBRARY_STYLE_INTEGRITY`) | Loaded with the script or with the module. |
| `library_module` | string | `''`: none | An ES module instead of the script (MapLibre GL JS 6 is published as modules only). No integrity check covers what a module imports. |
| `preserve_drawing_buffer` | bool | `true` | Keeps the canvas readable for `assets/capture.js`. |

A view's own MapLibre options: `$view->withOptions('maplibre', ['map' => ['pitch' => 45]])` -
handed to `new maplibregl.Map()` as they are, and part of the picture's fingerprint.

## Capabilities

| Capability | Supported |
|---|---|
| renderer (`MapRendererInterface`) | yes |
| static map, geocoder, tiles | no: `Registry::staticMap()`, `geocoder()` and `tiles()` refuse it with `NotSupportedException`; pair it with omnimap/openstreetmap for images made on the server or for geocoding |

Browser hosts: `hosts` (else the style's host), and the hosts of the library's script, module and
stylesheet (`tiles.openfreemap.org`, `unpkg.com` by default); cookies: declared `false`. Terms:
for an OpenFreeMap style (`https://tiles.openfreemap.org/...`) `https://openfreemap.org/tos/`, the
OpenStreetMap copyright and the OpenMapTiles licence; for any other style the OpenStreetMap
copyright only.

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

`Style::fetch($http, $url)` reads the style (it must be version 8 with `sources`) and, for each
source without `tiles` whose `url` is `http(s)`, that TileJSON's `tiles` (the TileJSON's URL kept
with them). `Style` holds `url`, `style` (the JSON) and `sources` (each source's URLs);
`hosts()` gives the hosts of the style's URL, every source URL, the glyphs and the sprite (a
string or a list of `{id, url}`), placeholders such as `{fontstack}` and `{z}` read as text, each
host once. `inspect()` reads it on each call: keep the result rather than calling it per request.

## Behaviour

**Description.** `describe()` returns `provider: maplibre`, `consent: MAP`, the view, `library`
(`script`, `scriptIntegrity`, `style`, `styleIntegrity`; or `module`, `style`, `styleIntegrity`
when `library_module` is set; empty values left out), `style` (the style's URL),
`options: {map, preserveDrawingBuffer}` and `attribution`.

**The live map (`assets/maplibre.js`).** Unless `window.maplibregl` exists: loads `library.style`
(with `integrity` and `crossorigin="anonymous"` when an integrity is set), then imports
`library.module` (its default export, else the module itself) or loads `library.script`; neither:
`omnimap: no MapLibre GL JS to load.`. Draws `new maplibregl.Map({container, style, center, zoom,
attributionControl: {compact: false}, preserveDrawingBuffer, canvasContextAttributes:
{preserveDrawingBuffer}, interactive: controls, ...map})`; adds a `NavigationControl` without
compass when the controls are on; one `maplibregl.Marker` per marker in its colour (`#d33b3b` by
default), its title as the marker's `title` and `aria-label`; dispatches `idle` on `window` the
first time the map is idle. With `controls: false` the map is not interactive at all (no pan, no
zoom), unlike the other drivers.

**Fingerprint.** `maplibre|` and the JSON of the style URL, centre, zoom, markers, controls and
the view's `maplibre` options.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnimap\MapHtml;
use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\Model\LatLng;
use Omnimap\Model\MapView;
use Omnimap\Model\Marker;
use Omnimap\Registry;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// A style of one's own, its vector source on a TileJSON elsewhere; HttpClient::create() in production.
$answers = [
    'https://maps.example.org/style.json' => ['version' => 8, 'sources' => ['base' => ['type' => 'vector', 'url' => 'https://tiles.example.net/planet']],
        'glyphs' => 'https://fonts.example.com/{fontstack}/{range}.pbf', 'sprite' => 'https://maps.example.org/sprite', 'layers' => []],
    'https://tiles.example.net/planet' => ['tilejson' => '3.0.0', 'tiles' => ['https://tiles.example.net/planet/{z}/{x}/{y}.pbf']],
];
$http = new MockHttpClient(static fn (string $method, string $url) => new MockResponse(json_encode($answers[$url]), ['response_headers' => ['content-type' => 'application/json']]));

$registry = new Registry([new MapLibreFactory($http)], [
    'free' => ['factory' => 'maplibre'],
    'own' => ['factory' => 'maplibre', 'options' => [
        'style' => 'https://maps.example.org/style.json',
        'attribution' => '&copy; OpenStreetMap contributors',
        'hosts' => 'maps.example.org, tiles.example.net, fonts.example.com',
        'library_module' => 'https://maps.example.org/maplibre-gl.mjs',
    ]],
]);

$view = (new MapView(new LatLng(48.5818, 7.7509), 15, [new Marker(new LatLng(48.5818, 7.7509), 'Cathédrale')], id: 'map'))
    ->withOptions('maplibre', ['map' => ['pitch' => 45, 'bearing' => -17]]);

$free = $registry->renderer('free')->describe($view);
echo $free['style'], "\n", json_encode(array_keys($free['library'])), "\n", json_encode($free['options']), "\n";
echo implode(', ', $registry->get('free')->capabilities()->browserHosts), "\n";
echo json_encode($registry->renderer('own')->describe($view)['library'], JSON_UNESCAPED_SLASHES), "\n";
echo implode(', ', $registry->get('own')->inspect()->hosts()), "\n";
echo implode(', ', $registry->get('own')->capabilities()->browserHosts), "\n";
echo implode(', ', $registry->get('own')->capabilities()->terms), "\n";
echo strlen((new MapHtml())->live('map', $free)), " bytes of markup\n";
```

```
https://tiles.openfreemap.org/styles/liberty
["script","scriptIntegrity","style","styleIntegrity"]
{"map":{"pitch":45,"bearing":-17},"preserveDrawingBuffer":true}
tiles.openfreemap.org, unpkg.com
{"module":"https://maps.example.org/maplibre-gl.mjs","style":"https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css","styleIntegrity":"sha384-uTttxo/aOKbdE5RlD/SPzSDoDmNvGlUYPjONi2MN/b7c9HPSvW07OIuyP7uL6jxK"}
maps.example.org, tiles.example.net, fonts.example.com
maps.example.org, tiles.example.net, fonts.example.com, unpkg.com
https://www.openstreetmap.org/copyright
1478 bytes of markup
```

(run on 2026-10-25 in omnimap's harness, the style and its TileJSON answered by the
`MockHttpClient`: nothing was fetched)

With `library_module` set and `library_style` left alone, the stylesheet still comes from unpkg,
which stays among the browser hosts: set `library_style` (and its integrity) to your own copy too.

In Symfony, once the factory is registered (see the warning above):

```yaml
omnimap:
    provider: maplibre
    secret: '%env(MAP_SECRET)%'
    providers:
        maplibre: { style: 'https://tiles.openfreemap.org/styles/positron' }
    cache:
        drive: local.uploads
        make: false            # no server-made pictures: they come from the browser's capture
```

## Errors

`Style::fetch()` / `inspect()`: an answer other than 200, or a body that is not a style
(version 8 and its sources) or not JSON, is a `ProviderException`; a request that got no answer an
`UnreachableException`; `inspect()` without the factory's HTTP client a `NotSupportedException`.
Describing a view throws nothing.

## Verified, and not

- Verified (`vendor/bin/phpunit` in ghcr.io/glitchr-studio/php:8.4, 2026-10-25, and omnimap's
  harness): omnimap's conformance suite (`Tests/MapLibreContractTest.php`); the description, a
  style of one's own and MapLibre as a module (`Tests/MapLibreProviderTest.php`); the style reader
  on OpenFreeMap's style and TileJSON recorded on 2026-10-25, and its errors (`Tests/StyleTest.php`);
  every class loaded with the package's requirements alone (`BareTest`). PHPStan at the highest
  level, empty baseline.
- Verified live, no key (`vendor/bin/phpunit --group live`, 2026-10-25): OpenFreeMap's style,
  its TileJSON, a vector tile of Strasbourg, a glyph range and the sprite, all on the declared
  host; unpkg's MapLibre GL JS 5.24.0 and its stylesheet against the SHA-384 hashes the browser
  checks, with the CORS header that check needs.
- Verified in a browser (headless Chrome 137 through Puppeteer 24.10, WebGL by SwiftShader,
  2026-10-25, as the README states): `assets/maplibre.js` drew that description through
  omnimap's `omnimap.js` - the map of Strasbourg from OpenFreeMap's tiles, the marker with its
  label, the attribution, its canvas readable, no script error.
- Not verified: MapLibre GL JS 6 loaded as a module (`library_module`), styles other than
  OpenFreeMap's, `assets/capture.js` sending a MapLibre picture to a host's cache.

## Responsibility

omnimap/maplibre is an unofficial client, affiliated with neither MapLibre nor OpenFreeMap. The MIT
licence covers the code only; whoever runs it complies with the terms of the style's services
(OpenFreeMap's terms, https://openfreemap.org/tos/, the OpenMapTiles and OpenStreetMap licences by
default: `Capabilities::$terms`) and shows their attribution. See the README's Responsibility
section.
