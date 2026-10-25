<?php

namespace Omnimap\MapLibre\Tests;

use Omnimap\Exception\ProviderException;
use Omnimap\Exception\UnreachableException;
use Omnimap\MapLibre\MapLibreFactory;
use Omnimap\MapLibre\MapLibreProvider;
use Omnimap\MapLibre\Style;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * OpenFreeMap's "liberty" style and its "planet" TileJSON, recorded on 2026-10-25 (the TileJSON cut
 * to its tiles, zooms, bounds and attribution: its vector_layers are left out).
 */
final class StyleTest extends TestCase
{
    public function testTheHostsAStyleMakesTheBrowserReach(): void
    {
        $requested = [];
        $http = new MockHttpClient(static function (string $method, string $url) use (&$requested): MockResponse {
            $requested[] = $url;

            return new MockResponse((string) file_get_contents(__DIR__.'/Fixtures/'.match ($url) {
                'https://tiles.openfreemap.org/styles/liberty' => 'openfreemap-liberty.json',
                'https://tiles.openfreemap.org/planet' => 'openfreemap-planet.json',
                default => throw new \LogicException("No recorded answer for $url."),
            }));
        });
        $provider = (new MapLibreFactory($http))->create();
        \assert($provider instanceof MapLibreProvider);
        $style = $provider->inspect();

        self::assertSame(['https://tiles.openfreemap.org/styles/liberty', 'https://tiles.openfreemap.org/planet'], $requested);
        self::assertSame(['tiles.openfreemap.org'], $style->hosts());
        self::assertSame(['https://tiles.openfreemap.org/planet/20261018_080001_pt/{z}/{x}/{y}.pbf', 'https://tiles.openfreemap.org/planet'], $style->sources['openmaptiles']);
        self::assertSame([], array_diff($style->hosts(), $provider->capabilities()->browserHosts), 'every host the style reaches is declared');
    }

    public function testWhatIsNotAStyleIsTheProvidersError(): void
    {
        foreach ([
            new MockResponse('{"version": 7, "sources": {}}'),
            new MockResponse('<!doctype html><title>Proxy</title>', ['response_headers' => ['content-type' => 'text/html']]),
            new MockResponse('[]'),
            new MockResponse('Not found', ['http_code' => 404]),
            new MockResponse('Busy', ['http_code' => 503]),
        ] as $i => $answer) {
            try {
                Style::fetch(new MockHttpClient($answer), 'https://maps.example.org/style.json');
                self::fail("Answer $i taken for a style.");
            } catch (ProviderException $e) {
                self::assertStringContainsString('https://maps.example.org/style.json', $e->getMessage());
            }
        }
        $this->expectException(UnreachableException::class);
        Style::fetch(new MockHttpClient(new MockResponse('', ['error' => 'Could not resolve host'])), 'https://maps.example.org/style.json');
    }
}
