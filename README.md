# omnimap/maplibre

MapLibre for [omnisense/omnimap](https://github.com/glitchr-omnisense/omnimap), under any PHP host:
the live map drawn by [MapLibre GL JS](https://maplibre.org) from a vector style - by default
[OpenFreeMap](https://openfreemap.org)'s "liberty", free, without a key and without cookies - and
the style read on the server to name every host it makes a visitor's browser reach. Plain PHP: no
framework in `require`.

```php
use Omnimap\MapHtml;
use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\Model\{LatLng, MapView, Marker};
use Omnimap\Registry;

$registry = new Registry([new MapLibreFactory()], ['maps' => ['factory' => 'maplibre', 'options' => []]]);
$view = new MapView(new LatLng(48.5818, 7.7509), 14, [new Marker(new LatLng(48.5818, 7.7509), 'Cathédrale')], id: 'map');

echo (new MapHtml())->live('map', $registry->renderer('maps')->describe($view));
```

In the browser, register the driver beside omnimap's script:

```js
import { register, mountAll } from '.../omnimap/assets/omnimap.js';
import maplibre from '.../omnimap/maplibre/assets/maplibre.js';
register('maplibre', maplibre);
mountAll();
```

A renderer only: vector tiles are drawn by the browser's WebGL, so the server makes no image of a
view and serves no raster tiles. The picture of a view still reaches omnimap's map cache, captured
in the browser (`assets/capture.js`): the map keeps its drawing buffer for it.

## Documentation

- [The manual](docs/index.md): options, capabilities, the style's hosts, behaviour, an example, errors
- [Contributing](CONTRIBUTING.md): the tests, the conformance suite, a release

## Verified, and not

- Verified (`vendor/bin/phpunit` in ghcr.io/glitchr-studio/php:8.4, 2026-10-25): omnimap's
  conformance suite (`Tests/MapLibreContractTest.php`, `Omnimap\Testing\ProviderContractTests`);
  the description, a style of one's own and MapLibre as a module; the style reader on
  OpenFreeMap's style and TileJSON recorded on 2026-10-25, and its errors; every class loaded with
  the package's requirements alone (`BareTest`). PHPStan at the highest level, empty baseline.
- Verified live, no key (`vendor/bin/phpunit --group live`, 2026-10-25): OpenFreeMap's style,
  its TileJSON, a vector tile of Strasbourg, a glyph range and the sprite, all on the declared
  host; unpkg's MapLibre GL JS 5.24.0 and its stylesheet against the SHA-384 hashes the browser
  checks, with the CORS header that check needs.
- Verified in a browser (headless Chrome 137 through Puppeteer 24.10, WebGL by SwiftShader,
  2026-10-25): `assets/maplibre.js` drew that description through omnimap's `omnimap.js` - the
  map of Strasbourg from OpenFreeMap's tiles, the marker with its label, the attribution, its
  canvas readable (drawing buffer kept), no script error.
- Not verified: MapLibre GL JS 6 loaded as a module (`library_module`), styles other than
  OpenFreeMap's, `assets/capture.js` sending a MapLibre picture to a host's cache.

## Responsibility

omnimap/maplibre is an unofficial client, affiliated with neither MapLibre nor OpenFreeMap. The
MIT licence covers the code only and grants no right to any service or its data; whoever runs it
complies with the terms of the style's services (OpenFreeMap's terms, the OpenMapTiles and
OpenStreetMap licences by default: `Capabilities::$terms`) and shows their attribution.

## Licence

MIT.
