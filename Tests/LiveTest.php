<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\MapLibre\MapLibreProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Against the real services, no key needed (vendor/bin/phpunit --group live): OpenFreeMap's style, its
 * TileJSON, one vector tile, a glyph range and the sprite; unpkg's MapLibre GL JS against the hashes the
 * factory gives the browser. A handful of requests, within OpenFreeMap's terms.
 */
#[Group('live')]
final class LiveTest extends TestCase
{
    public function testOpenFreeMapsStyleTilesGlyphsAndSprite(): void
    {
        $http = HttpClient::create(['headers' => ['User-Agent' => 'omnimap-maplibre-tests/0.1 (+https://github.com/glitchr-studio/omnimap-maplibre)'], 'timeout' => 20]);
        $provider = (new MapLibreFactory($http))->create();
        \assert($provider instanceof MapLibreProvider);
        $style = $provider->inspect();

        self::assertSame([], array_diff($style->hosts(), $provider->capabilities()->browserHosts), 'the style reaches only declared hosts');
        $tiles = array_values(array_filter($style->sources['openmaptiles'] ?? [], static fn (string $url) => str_contains($url, '{z}')));
        self::assertNotSame([], $tiles);
        $tile = $http->request('GET', str_replace(['{z}', '{x}', '{y}'], ['12', '2136', '1405'], $tiles[0])); // Strasbourg
        self::assertSame(200, $tile->getStatusCode());
        self::assertGreaterThan(1000, \strlen($tile->getContent()), 'a vector tile of a city');

        $glyphs = $style->style['glyphs'] ?? null;
        $layers = $style->style['layers'] ?? null;
        self::assertIsString($glyphs);
        self::assertIsArray($layers);
        $font = null;
        foreach ($layers as $layer) {
            $fonts = \is_array($layer) && \is_array($layer['layout'] ?? null) ? ($layer['layout']['text-font'] ?? null) : null;
            if (\is_array($fonts) && \is_string($fonts[0] ?? null)) {
                $font = $fonts[0];
                break;
            }
        }
        self::assertIsString($font, 'a layer with text');
        self::assertSame(200, $http->request('GET', str_replace(['{fontstack}', '{range}'], [rawurlencode($font), '0-255'], $glyphs))->getStatusCode());
        $sprite = $style->style['sprite'] ?? null;
        self::assertIsString($sprite);
        self::assertSame(200, $http->request('GET', $sprite.'.json')->getStatusCode());
    }

    public function testTheLibraryTheBrowserLoadsMatchesItsHashes(): void
    {
        $http = HttpClient::create(['timeout' => 30]);
        foreach ([MapLibreFactory::LIBRARY_SCRIPT => MapLibreFactory::LIBRARY_SCRIPT_INTEGRITY, MapLibreFactory::LIBRARY_STYLE => MapLibreFactory::LIBRARY_STYLE_INTEGRITY] as $url => $integrity) {
            $response = $http->request('GET', $url);
            self::assertSame(200, $response->getStatusCode());
            self::assertSame('*', $response->getHeaders()['access-control-allow-origin'][0] ?? null, 'crossorigin="anonymous" needs CORS for the integrity check');
            self::assertSame($integrity, 'sha384-'.base64_encode(hash('sha384', $response->getContent(), true)), $url);
        }
    }
}
