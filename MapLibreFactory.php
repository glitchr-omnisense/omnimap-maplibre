<?php

namespace Omnimap\MapLibre;

use Omnimap\Config;
use Omnimap\ProviderFactory;
use Omnimap\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MapLibre, from its options:
 *
 *   style        the MapLibre style's URL (a style JSON: its vector tiles, glyphs and sprite). The default
 *                is OpenFreeMap's "liberty" style: free, without a key, without cookies
 *   attribution  as HTML, shown on the map (the default: OpenFreeMap, OpenMapTiles and the OpenStreetMap
 *                contributors, as the data's licence requires)
 *   hosts        the hosts the visitor's browser reaches for the style, its tiles, glyphs and sprite -
 *                comma separated or a list; the style's own host by default. Style::hosts() reads them
 *                from the style itself: check them when you change it
 *   library_script, library_style, library_script_integrity, library_style_integrity
 *                where the browser gets MapLibre GL JS (unpkg, 5.24.0, with its SHA-384 hashes): serve
 *                your own copy to keep the visitor's browser off a CDN
 *   library_module  an ES module instead (MapLibre GL JS 6 is published as modules only); no integrity
 *                   check is possible on what it imports, so the script stays the default
 *   preserve_drawing_buffer  true (the default) lets assets/capture.js take the live map's picture
 *
 * The HTTP client is only for Style (reading a style's hosts on the server); drawing needs none.
 */
final class MapLibreFactory extends ProviderFactory
{
    public const OPENFREEMAP_STYLE = 'https://tiles.openfreemap.org/styles/liberty';
    public const OPENFREEMAP_ATTRIBUTION = '<a href="https://openfreemap.org" target="_blank">OpenFreeMap</a> <a href="https://www.openmaptiles.org/" target="_blank">&copy; OpenMapTiles</a> Data from <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>';
    public const LIBRARY_SCRIPT = 'https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js';
    public const LIBRARY_SCRIPT_INTEGRITY = 'sha384-5+cfbwT0iiub6VsQAdn6yz16nr6sDiQoHx6tm4O8OVYXHYOxcffFmCJBL0dgdvGp';
    public const LIBRARY_STYLE = 'https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css';
    public const LIBRARY_STYLE_INTEGRITY = 'sha384-uTttxo/aOKbdE5RlD/SPzSDoDmNvGlUYPjONi2MN/b7c9HPSvW07OIuyP7uL6jxK';

    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnimap.factory_name' => 'maplibre',
            'omnimap.factory_title' => 'MapLibre',
            'omnimap.required_options' => [],
            'style' => self::OPENFREEMAP_STYLE,
            'attribution' => self::OPENFREEMAP_ATTRIBUTION,
            'hosts' => [],
            'library_script' => self::LIBRARY_SCRIPT,
            'library_script_integrity' => self::LIBRARY_SCRIPT_INTEGRITY,
            'library_style' => self::LIBRARY_STYLE,
            'library_style_integrity' => self::LIBRARY_STYLE_INTEGRITY,
            'library_module' => '',
            'preserve_drawing_buffer' => true,
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        $hosts = $c->get('hosts');
        $hosts = \is_string($hosts) ? explode(',', $hosts) : (\is_array($hosts) ? $hosts : []);
        $module = $c->string('library_module');

        return new MapLibreProvider(
            style: (string) $c->string('style'),
            attribution: (string) $c->string('attribution'),
            hosts: array_values(array_filter(array_map(static fn ($host) => \is_scalar($host) ? trim((string) $host) : '', $hosts))),
            library: array_filter(null !== $module ? ['module' => $module, 'style' => $c->string('library_style'), 'styleIntegrity' => $c->string('library_style_integrity')] : [
                'script' => $c->string('library_script'),
                'scriptIntegrity' => $c->string('library_script_integrity'),
                'style' => $c->string('library_style'),
                'styleIntegrity' => $c->string('library_style_integrity'),
            ]),
            preserveDrawingBuffer: null === $c->get('preserve_drawing_buffer') || $c->bool('preserve_drawing_buffer'),
            http: $this->http,
        );
    }
}
